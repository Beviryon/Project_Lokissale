<?php
/**
 * Gestion des produits (CRUD)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestion des produits';
$pageCSS = 'admin.css';
$db = getDB();
$errors = [];
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id_produit = $action === 'edit' ? (int)($_POST['id_produit'] ?? 0) : 0;
        $id_salle = (int)($_POST['id_salle'] ?? 0);
        $date_arrivee = $_POST['date_arrivee'] ?? '';
        $date_depart = $_POST['date_depart'] ?? '';
        $prix = (int)($_POST['prix'] ?? 0); // Prix en centimes
        $id_promo = !empty($_POST['id_promo']) ? (int)$_POST['id_promo'] : null;
        
        // Validation
        if ($id_salle <= 0) {
            $errors[] = "Veuillez sélectionner une salle.";
        }
        
        if (empty($date_arrivee)) {
            $errors[] = "La date d'arrivée est requise.";
        }
        
        if (empty($date_depart)) {
            $errors[] = "La date de départ est requise.";
        }
        
        // Validation des dates
        if (!empty($date_arrivee) && !empty($date_depart)) {
            $date_arrivee_obj = new DateTime($date_arrivee);
            $date_depart_obj = new DateTime($date_depart);
            $now = new DateTime();
            
            // La date d'arrivée doit être supérieure à la date du jour
            if ($date_arrivee_obj <= $now) {
                $errors[] = "La date d'arrivée doit être supérieure à la date du jour.";
            }
            
            // La date de départ doit être supérieure à la date d'arrivée
            if ($date_depart_obj <= $date_arrivee_obj) {
                $errors[] = "La date de départ doit être supérieure à la date d'arrivée.";
            }
        }
        
        if ($prix <= 0) {
            $errors[] = "Le prix doit être supérieur à 0.";
        }
        
        // Vérifier les chevauchements de dates pour la même salle
        if (empty($errors) && $id_salle > 0) {
            $stmt = $db->prepare("
                SELECT id_produit 
                FROM produit 
                WHERE id_salle = ? 
                AND id_produit != ?
                AND etat = 1
                AND (
                    (date_arrivee <= ? AND date_depart >= ?) OR
                    (date_arrivee <= ? AND date_depart >= ?) OR
                    (date_arrivee >= ? AND date_depart <= ?)
                )
            ");
            $stmt->execute([
                $id_salle,
                $id_produit,
                $date_arrivee, $date_arrivee,
                $date_depart, $date_depart,
                $date_arrivee, $date_depart
            ]);
            
            if ($stmt->fetch()) {
                $errors[] = "Cette salle est déjà réservée sur cette période. Les dates se chevauchent avec un autre produit.";
            }
        }
        
        if (empty($errors)) {
            try {
                // Convertir le prix en centimes si nécessaire (si l'utilisateur entre en euros)
                // On suppose que l'admin entre le prix en euros, on le convertit en centimes
                if ($prix < 1000) { // Si le prix est inférieur à 1000, on suppose qu'il est en euros
                    $prix = $prix * 100;
                }
                
                if ($action === 'add') {
                    $stmt = $db->prepare("
                        INSERT INTO produit (id_salle, date_arrivee, date_depart, prix, id_promo, etat)
                        VALUES (?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$id_salle, $date_arrivee, $date_depart, $prix, $id_promo]);
                    $success = 'Produit ajouté avec succès !';
                } else {
                    $stmt = $db->prepare("
                        UPDATE produit 
                        SET id_salle = ?, date_arrivee = ?, date_depart = ?, prix = ?, id_promo = ?
                        WHERE id_produit = ?
                    ");
                    $stmt->execute([$id_salle, $date_arrivee, $date_depart, $prix, $id_promo, $id_produit]);
                    $success = 'Produit modifié avec succès !';
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id_produit = (int)($_POST['id_produit'] ?? 0);
        
        // Vérifier si le produit est dans une commande
        $stmt = $db->prepare("SELECT COUNT(*) FROM details_commande WHERE id_produit = ?");
        $stmt->execute([$id_produit]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Impossible de supprimer : ce produit est associé à une commande.";
        } else {
            $stmt = $db->prepare("DELETE FROM produit WHERE id_produit = ?");
            $stmt->execute([$id_produit]);
            $success = 'Produit supprimé avec succès !';
        }
    }
}

// Récupération des paramètres de tri
$sort_by = $_GET['sort'] ?? 'date_arrivee';
$sort_order = $_GET['order'] ?? 'ASC';

// Validation du tri
$allowed_sorts = ['date_arrivee', 'date_depart', 'prix'];
if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'date_arrivee';
}
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

// Récupération des produits avec jointures
$sql = "
    SELECT p.*, 
           s.titre as salle_titre, 
           s.ville, 
           s.capacite,
           pr.code_promo,
           pr.reduction
    FROM produit p
    JOIN salle s ON p.id_salle = s.id_salle
    LEFT JOIN promotion pr ON p.id_promo = pr.id_promo
    ORDER BY p.$sort_by $sort_order
";
$stmt = $db->query($sql);
$produits = $stmt->fetchAll();

// Récupération des salles pour le formulaire
$stmt = $db->query("SELECT id_salle, titre, ville FROM salle ORDER BY ville, titre");
$salles = $stmt->fetchAll();

// Récupération des promotions pour le formulaire
$stmt = $db->query("SELECT id_promo, code_promo, reduction FROM promotion WHERE actif = 1 ORDER BY code_promo");
$promotions = $stmt->fetchAll();

// Produit à éditer
$produit_edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("
        SELECT p.*, s.titre as salle_titre
        FROM produit p
        JOIN salle s ON p.id_salle = s.id_salle
        WHERE p.id_produit = ?
    ");
    $stmt->execute([$id]);
    $produit_edit = $stmt->fetch();
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <h1 class="text-center mb-2">Gestion des produits</h1>
    
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
    
    <!-- Menu d'actions -->
    <div class="card mb-2">
        <h2 class="card-title">Actions</h2>
        <div class="admin-actions">
            <a href="#formulaire" class="btn btn-primary" onclick="document.getElementById('formulaire').scrollIntoView({behavior: 'smooth'});">
                Ajouter un produit
            </a>
            <a href="#liste" class="btn btn-secondary" onclick="document.getElementById('liste').scrollIntoView({behavior: 'smooth'});">
                Affichage des produits
            </a>
        </div>
    </div>
    
    <!-- Formulaire d'ajout/modification -->
    <div id="formulaire" class="card mb-2">
        <h2 class="card-title"><?php echo $produit_edit ? 'Modifier un produit' : 'Ajouter un produit'; ?></h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?php echo $produit_edit ? 'edit' : 'add'; ?>">
            <?php if ($produit_edit): ?>
                <input type="hidden" name="id_produit" value="<?php echo $produit_edit['id_produit']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_salle">Salle *</label>
                    <select id="id_salle" name="id_salle" required>
                        <option value="">-- Sélectionner une salle --</option>
                        <?php foreach ($salles as $salle): ?>
                            <option value="<?php echo $salle['id_salle']; ?>" 
                                    <?php echo (($produit_edit['id_salle'] ?? 0) == $salle['id_salle']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($salle['titre'] . ' - ' . $salle['ville']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_arrivee">Date d'arrivée *</label>
                    <input type="datetime-local" id="date_arrivee" name="date_arrivee" required
                           value="<?php echo $produit_edit ? date('Y-m-d\TH:i', strtotime($produit_edit['date_arrivee'])) : ''; ?>">
                    <small>Doit être supérieure à la date du jour</small>
                </div>
                
                <div class="form-group">
                    <label for="date_depart">Date de départ *</label>
                    <input type="datetime-local" id="date_depart" name="date_depart" required
                           value="<?php echo $produit_edit ? date('Y-m-d\TH:i', strtotime($produit_edit['date_depart'])) : ''; ?>">
                    <small>Doit être supérieure à la date d'arrivée</small>
                </div>
                
                <div class="form-group">
                    <label for="prix">Prix (en euros) *</label>
                    <input type="number" id="prix" name="prix" required min="1" step="0.01"
                           value="<?php echo $produit_edit ? number_format($produit_edit['prix'] / 100, 2, '.', '') : ''; ?>">
                    <small>Le prix sera converti en centimes en base de données</small>
                </div>
                
                <div class="form-group">
                    <label for="id_promo">Code promotionnel (optionnel)</label>
                    <select id="id_promo" name="id_promo">
                        <option value="">Aucun code promo</option>
                        <?php foreach ($promotions as $promo): ?>
                            <option value="<?php echo $promo['id_promo']; ?>" 
                                    <?php echo (($produit_edit['id_promo'] ?? null) == $promo['id_promo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($promo['code_promo'] . ' (-' . $promo['reduction'] . '%)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary"><?php echo $produit_edit ? 'Modifier' : 'Ajouter'; ?></button>
            <?php if ($produit_edit): ?>
                <a href="gestion_produits.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Liste des produits -->
    <div id="liste" class="card">
        <h2 class="card-title">Affichage des produits (<?php echo count($produits); ?>)</h2>
        
        <?php if (empty($produits)): ?>
            <p>Aucun produit enregistré.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Salle</th>
                            <th>Ville</th>
                            <th>
                                <a href="?sort=date_arrivee&order=<?php echo ($sort_by === 'date_arrivee' && $sort_order === 'ASC') ? 'DESC' : 'ASC'; ?>">
                                    Date arrivée
                                    <?php if ($sort_by === 'date_arrivee'): ?>
                                        <?php echo $sort_order === 'ASC' ? '↑' : '↓'; ?>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=date_depart&order=<?php echo ($sort_by === 'date_depart' && $sort_order === 'ASC') ? 'DESC' : 'ASC'; ?>">
                                    Date départ
                                    <?php if ($sort_by === 'date_depart'): ?>
                                        <?php echo $sort_order === 'ASC' ? '↑' : '↓'; ?>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=prix&order=<?php echo ($sort_by === 'prix' && $sort_order === 'ASC') ? 'DESC' : 'ASC'; ?>">
                                    Prix
                                    <?php if ($sort_by === 'prix'): ?>
                                        <?php echo $sort_order === 'ASC' ? '↑' : '↓'; ?>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Promo</th>
                            <th>État</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produits as $produit): ?>
                            <tr>
                                <td><?php echo $produit['id_produit']; ?></td>
                                <td><?php echo htmlspecialchars($produit['salle_titre']); ?></td>
                                <td><?php echo htmlspecialchars($produit['ville']); ?></td>
                                <td><?php echo formatDate($produit['date_arrivee'], 'd/m/Y H:i'); ?></td>
                                <td><?php echo formatDate($produit['date_depart'], 'd/m/Y H:i'); ?></td>
                                <td><?php echo formatPrice($produit['prix']); ?></td>
                                <td>
                                    <?php if ($produit['code_promo']): ?>
                                        <span class="badge"><?php echo htmlspecialchars($produit['code_promo']); ?></span>
                                        <small>(-<?php echo $produit['reduction']; ?>%)</small>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($produit['etat'] == 1): ?>
                                        <span class="status-disponible">Disponible</span>
                                    <?php else: ?>
                                        <span class="status-reserve">Réservé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $produit['id_produit']; ?>" class="btn btn-primary btn-small">Modifier</a>
                                    <form method="POST" action="" class="inline-form" 
                                          onsubmit="return confirm('Supprimer ce produit ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
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