<?php
/**
 * Gestion des salles (CRUD)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestion des salles';
$pageCSS = 'admin.css';
$db = getDB();
$errors = [];
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id_salle = $action === 'edit' ? (int)($_POST['id_salle'] ?? 0) : 0;
        $pays = cleanInput($_POST['pays'] ?? 'France');
        $ville = cleanInput($_POST['ville'] ?? '');
        $adresse = cleanInput($_POST['adresse'] ?? '');
        $cp = cleanInput($_POST['cp'] ?? '');
        $titre = cleanInput($_POST['titre'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $capacite = (int)($_POST['capacite'] ?? 0);
        $categorie = $_POST['categorie'] ?? '';
        
        // Validation
        if (empty($ville)) $errors[] = "La ville est requise.";
        if (empty($adresse)) $errors[] = "L'adresse est requise.";
        if (empty($cp) || !preg_match('/^\d{5}$/', $cp)) $errors[] = "Code postal invalide.";
        if (empty($titre)) $errors[] = "Le titre est requis.";
        if (empty($description)) $errors[] = "La description est requise.";
        if ($capacite <= 0) $errors[] = "La capacité doit être supérieure à 0.";
        if (!in_array($categorie, ['reunion', 'formation', 'seminaire', 'conference', 'autre'])) {
            $errors[] = "Catégorie invalide.";
        }
        
        // Gestion de l'upload de photo
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = uploadImage($_FILES['photo'], 'salles');
            if (!$photo) {
                $errors[] = "Erreur lors de l'upload de l'image.";
            }
        } elseif ($action === 'edit' && empty($errors)) {
            // Conserver l'ancienne photo si pas de nouvelle
            $stmt = $db->prepare("SELECT photo FROM salle WHERE id_salle = ?");
            $stmt->execute([$id_salle]);
            $old = $stmt->fetch();
            $photo = $old['photo'] ?? null;
        }
        
        if (empty($errors)) {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("
                        INSERT INTO salle (pays, ville, adresse, cp, titre, description, photo, capacite, categorie)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$pays, $ville, $adresse, $cp, $titre, $description, $photo, $capacite, $categorie]);
                    $success = 'Salle ajoutée avec succès !';
                } else {
                    $stmt = $db->prepare("
                        UPDATE salle 
                        SET pays = ?, ville = ?, adresse = ?, cp = ?, titre = ?, description = ?, 
                            photo = ?, capacite = ?, categorie = ?
                        WHERE id_salle = ?
                    ");
                    $stmt->execute([$pays, $ville, $adresse, $cp, $titre, $description, $photo, $capacite, $categorie, $id_salle]);
                    $success = 'Salle modifiée avec succès !';
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de l'enregistrement.";
            }
        }
    } elseif ($action === 'delete') {
        $id_salle = (int)($_POST['id_salle'] ?? 0);
        
        // Vérifier qu'il n'y a pas de produits associés
        $stmt = $db->prepare("SELECT COUNT(*) FROM produit WHERE id_salle = ?");
        $stmt->execute([$id_salle]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Impossible de supprimer : des produits sont associés à cette salle.";
        } else {
            // Supprimer la photo si elle existe
            $stmt = $db->prepare("SELECT photo FROM salle WHERE id_salle = ?");
            $stmt->execute([$id_salle]);
            $salle = $stmt->fetch();
            if ($salle && $salle['photo']) {
                deleteFile($salle['photo'], 'salles');
            }
            
            $stmt = $db->prepare("DELETE FROM salle WHERE id_salle = ?");
            $stmt->execute([$id_salle]);
            $success = 'Salle supprimée avec succès !';
        }
    }
}

// Récupération des salles
$stmt = $db->query("SELECT * FROM salle ORDER BY id_salle DESC");
$salles = $stmt->fetchAll();

// Salle à éditer
$salle_edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM salle WHERE id_salle = ?");
    $stmt->execute([$id]);
    $salle_edit = $stmt->fetch();
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <h1 class="text-center mb-2">Gestion des salles</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="card error-box">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="card success-box">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Formulaire d'ajout/modification -->
    <div class="card mb-2">
        <h2 class="card-title"><?php echo $salle_edit ? 'Modifier une salle' : 'Ajouter une salle'; ?></h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $salle_edit ? 'edit' : 'add'; ?>">
            <?php if ($salle_edit): ?>
                <input type="hidden" name="id_salle" value="<?php echo $salle_edit['id_salle']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="titre">Titre *</label>
                    <input type="text" id="titre" name="titre" required 
                           value="<?php echo htmlspecialchars($salle_edit['titre'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="categorie">Catégorie *</label>
                    <select id="categorie" name="categorie" required>
                        <option value="">-- Sélectionner --</option>
                        <?php 
                        $categories = ['reunion', 'formation', 'seminaire', 'conference', 'autre'];
                        foreach ($categories as $cat): 
                        ?>
                            <option value="<?php echo $cat; ?>" 
                                    <?php echo (($salle_edit['categorie'] ?? '') === $cat) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ville">Ville *</label>
                    <input type="text" id="ville" name="ville" required 
                           value="<?php echo htmlspecialchars($salle_edit['ville'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="cp">Code postal *</label>
                    <input type="text" id="cp" name="cp" required pattern="[0-9]{5}" maxlength="5"
                           value="<?php echo htmlspecialchars($salle_edit['cp'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="adresse">Adresse *</label>
                    <input type="text" id="adresse" name="adresse" required 
                           value="<?php echo htmlspecialchars($salle_edit['adresse'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="capacite">Capacité *</label>
                    <input type="number" id="capacite" name="capacite" required min="1"
                           value="<?php echo $salle_edit['capacite'] ?? ''; ?>">
                </div>
                
                <div class="form-group form-group-full">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required rows="5"><?php echo htmlspecialchars($salle_edit['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="photo">Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                    <?php if ($salle_edit && $salle_edit['photo']): ?>
                        <p>Photo actuelle : <?php echo htmlspecialchars($salle_edit['photo']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary"><?php echo $salle_edit ? 'Modifier' : 'Ajouter'; ?></button>
            <?php if ($salle_edit): ?>
                <a href="gestion-salles.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Liste des salles -->
    <div class="card">
        <h2 class="card-title">Liste des salles (<?php echo count($salles); ?>)</h2>
        
        <?php if (empty($salles)): ?>
            <p>Aucune salle enregistrée.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Titre</th>
                            <th>Ville</th>
                            <th>Capacité</th>
                            <th>Catégorie</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salles as $salle): ?>
                            <tr>
                                <td><?php echo $salle['id_salle']; ?></td>
                                <td>
                                    <?php if ($salle['photo']): ?>
                                        <img src="<?php echo UPLOAD_URL . 'salles/' . htmlspecialchars($salle['photo']); ?>" 
                                             alt="" class="thumbnail-image"
                                             onerror="this.src='../assets/images/placeholder.jpg'">
                                    <?php else: ?>
                                        <span>Pas d'image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($salle['titre']); ?></td>
                                <td><?php echo htmlspecialchars($salle['ville']); ?></td>
                                <td><?php echo $salle['capacite']; ?></td>
                                <td><?php echo ucfirst($salle['categorie']); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $salle['id_salle']; ?>" class="btn btn-primary btn-small">Modifier</a>
                                    <form method="POST" action="" class="inline-form" 
                                          onsubmit="return confirm('Supprimer cette salle ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_salle" value="<?php echo $salle['id_salle']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>