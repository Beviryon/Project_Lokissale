<?php
/**
 * Gestion des membres (gestion_membres.php)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestion des membres';
$pageCSS = 'admin.css';
$db = getDB();
$errors = [];
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id_membre = (int)($_POST['id_membre'] ?? 0);
        
        // Vérifier qu'on ne supprime pas soi-même
        if ($id_membre == $_SESSION['membre']['id_membre']) {
            $errors[] = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            // Vérifier s'il y a des commandes
            $stmt = $db->prepare("SELECT COUNT(*) FROM commande WHERE id_membre = ?");
            $stmt->execute([$id_membre]);
            $nb_commandes = $stmt->fetchColumn();
            
            if ($nb_commandes > 0) {
                $errors[] = "Impossible de supprimer : ce membre a des commandes associées.";
            } else {
                $stmt = $db->prepare("DELETE FROM membre WHERE id_membre = ?");
                $stmt->execute([$id_membre]);
                $success = 'Membre supprimé avec succès !';
            }
        }
    } elseif ($action === 'create_admin') {
        // Création d'un nouveau compte administrateur
        $pseudo = cleanInput($_POST['pseudo'] ?? '');
        $mdp = $_POST['mdp'] ?? '';
        $mdp_confirm = $_POST['mdp_confirm'] ?? '';
        $nom = cleanInput($_POST['nom'] ?? '');
        $prenom = cleanInput($_POST['prenom'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $sexe = $_POST['sexe'] ?? '';
        $ville = cleanInput($_POST['ville'] ?? '');
        $cp = cleanInput($_POST['cp'] ?? '');
        $adresse = cleanInput($_POST['adresse'] ?? '');
        
        // Validation
        if (empty($pseudo) || strlen($pseudo) < 3 || strlen($pseudo) > 15) {
            $errors[] = "Le pseudo doit contenir entre 3 et 15 caractères.";
        }
        
        if (empty($mdp) || strlen($mdp) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }
        
        if ($mdp !== $mdp_confirm) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        
        if (!isValidEmail($email)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        // Vérifier l'unicité
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id_membre FROM membre WHERE pseudo = ? OR email = ?");
            $stmt->execute([$pseudo, $email]);
            if ($stmt->fetch()) {
                $errors[] = "Ce pseudo ou cet email est déjà utilisé.";
            }
        }
        
        if (empty($errors)) {
            $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
            try {
                $stmt = $db->prepare("
                    INSERT INTO membre (pseudo, mdp, nom, prenom, email, sexe, ville, cp, adresse, statut)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$pseudo, $mdp_hash, $nom, $prenom, $email, $sexe, $ville, $cp, $adresse]);
                $success = 'Compte administrateur créé avec succès !';
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la création du compte.";
            }
        }
    }
}

// Récupération de tous les membres
$stmt = $db->query("
    SELECT m.*, 
           COUNT(DISTINCT c.id_commande) as nb_commandes
    FROM membre m
    LEFT JOIN commande c ON m.id_membre = c.id_membre
    GROUP BY m.id_membre
    ORDER BY m.date_enregistrement DESC
");
$membres = $stmt->fetchAll();

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <h1 class="text-center mb-2">Gestion des membres</h1>
    
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
    
    <!-- Zone 4 : Formulaire création compte admin -->
    <div class="card mb-2">
        <h2 class="card-title">Créer un nouveau compte administrateur</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_admin">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="pseudo">Pseudo *</label>
                    <input type="text" id="pseudo" name="pseudo" required 
                           minlength="3" maxlength="15"
                           value="<?php echo htmlspecialchars($_POST['pseudo'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mdp">Mot de passe *</label>
                    <input type="password" id="mdp" name="mdp" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="mdp_confirm">Confirmer le mot de passe *</label>
                    <input type="password" id="mdp_confirm" name="mdp_confirm" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" required 
                           value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" required 
                           value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="sexe">Sexe *</label>
                    <select id="sexe" name="sexe" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="m" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'm') ? 'selected' : ''; ?>>Masculin</option>
                        <option value="f" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'f') ? 'selected' : ''; ?>>Féminin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ville">Ville *</label>
                    <input type="text" id="ville" name="ville" required 
                           value="<?php echo htmlspecialchars($_POST['ville'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="cp">Code postal *</label>
                    <input type="text" id="cp" name="cp" required pattern="[0-9]{5}" maxlength="5"
                           value="<?php echo htmlspecialchars($_POST['cp'] ?? ''); ?>">
                </div>
                
                <div class="form-group form-group-full">
                    <label for="adresse">Adresse *</label>
                    <input type="text" id="adresse" name="adresse" required 
                           value="<?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Créer le compte administrateur</button>
        </form>
    </div>
    
    <!-- Zone 3 : Affichage des membres -->
    <div class="card">
        <h2 class="card-title">Liste des membres (<?php echo count($membres); ?>)</h2>
        
        <?php if (empty($membres)): ?>
            <p>Aucun membre enregistré.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pseudo</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Ville</th>
                            <th>Statut</th>
                            <th>Nb Commandes</th>
                            <th>Date inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membres as $membre): ?>
                            <tr>
                                <td><?php echo $membre['id_membre']; ?></td>
                                <td><?php echo htmlspecialchars($membre['pseudo']); ?></td>
                                <td><?php echo htmlspecialchars($membre['nom']); ?></td>
                                <td><?php echo htmlspecialchars($membre['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($membre['email']); ?></td>
                                <td><?php echo htmlspecialchars($membre['ville']); ?></td>
                                <td>
                                    <?php if ($membre['statut'] == 1): ?>
                                        <span class="status-admin">Admin</span>
                                    <?php else: ?>
                                        <span>Membre</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $membre['nb_commandes']; ?></td>
                                <td><?php echo formatDate($membre['date_enregistrement'], 'd/m/Y'); ?></td>
                                <td>
                                    <?php if ($membre['id_membre'] != $_SESSION['membre']['id_membre']): ?>
                                        <form method="POST" action="" class="inline-form" 
                                              onsubmit="return confirm('Supprimer ce membre ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_membre" value="<?php echo $membre['id_membre']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small" 
                                                    title="Supprimer">
                                                X
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="status-empty">-</span>
                                    <?php endif; ?>
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