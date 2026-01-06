<?php
/**
 * Page Modifier le profil
 * Permet aux utilisateurs de modifier leurs informations personnelles
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
requireLogin();

$pageTitle = 'Modifier mon profil - LOKISALLE';
$pageCSS = 'profil.css';

$db = getDB();
$id_membre = $_SESSION['membre']['id_membre'];

// Récupération des informations actuelles
$stmt = $db->prepare("SELECT * FROM membre WHERE id_membre = ?");
$stmt->execute([$id_membre]);
$membre = $stmt->fetch();

if (!$membre) {
    redirect('pages/profil.php', 'Erreur : membre introuvable.');
}

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $nom = cleanInput($_POST['nom'] ?? '');
    $prenom = cleanInput($_POST['prenom'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $ville = cleanInput($_POST['ville'] ?? '');
    $cp = cleanInput($_POST['cp'] ?? '');
    $adresse = cleanInput($_POST['adresse'] ?? '');
    
    // Validation
    if (empty($nom) || strlen($nom) < 2) {
        $errors[] = "Le nom est requis (minimum 2 caractères).";
    }
    
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors[] = "Le prénom est requis (minimum 2 caractères).";
    }
    
    if (!isValidEmail($email)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    
    if (empty($ville)) {
        $errors[] = "La ville est requise.";
    }
    
    if (empty($cp) || !preg_match('/^\d{5}$/', $cp)) {
        $errors[] = "Le code postal doit contenir 5 chiffres.";
    }
    
    if (empty($adresse)) {
        $errors[] = "L'adresse est requise.";
    }
    
    // Vérification de l'unicité de l'email (si changé)
    if (empty($errors) && $email !== $membre['email']) {
        $stmt = $db->prepare("SELECT id_membre FROM membre WHERE email = ? AND id_membre != ?");
        $stmt->execute([$email, $id_membre]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé par un autre compte.";
        }
    }
    
    // Mise à jour en base de données
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE membre 
                SET nom = ?, prenom = ?, email = ?, ville = ?, cp = ?, adresse = ?
                WHERE id_membre = ?
            ");
            
            $stmt->execute([$nom, $prenom, $email, $ville, $cp, $adresse, $id_membre]);
            
            // Mettre à jour la session avec les nouvelles informations
            $stmt = $db->prepare("SELECT * FROM membre WHERE id_membre = ?");
            $stmt->execute([$id_membre]);
            $membre_updated = $stmt->fetch();
            
            $_SESSION['membre'] = $membre_updated;
            
            redirect('pages/profil.php', 'Vos informations ont été mises à jour avec succès !');
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour. Veuillez réessayer.";
        }
    }
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <p>&gt;&gt; <a href="<?php echo SITE_URL; ?>/pages/profil.php">Profil</a> &gt;&gt; Modifier</p>
    </div>
    
    <div class="profil-layout" style="grid-template-columns: 1fr; max-width: 800px; margin: 2rem auto;">
        <div class="zone-3-informations">
            <h2>Modifier mes informations</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" style="margin-top: 2rem;">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="pseudo" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-color);">Pseudo</label>
                    <input type="text" id="pseudo" name="pseudo" 
                           value="<?php echo htmlspecialchars($membre['pseudo']); ?>" 
                           disabled 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; background-color: #f5f5f5; color: #666;">
                    <p class="form-help" style="font-size: 0.9rem; color: #666; margin-top: 0.25rem;">Le pseudo ne peut pas être modifié</p>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="nom" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-color);">Nom *</label>
                    <input type="text" id="nom" name="nom" required 
                           value="<?php echo htmlspecialchars($membre['nom'] ?? ''); ?>"
                           minlength="2"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="prenom" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-color);">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" required 
                           value="<?php echo htmlspecialchars($membre['prenom'] ?? ''); ?>"
                           minlength="2"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-color);">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($membre['email'] ?? ''); ?>"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="ville" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-color);">Ville *</label>
                    <input type="text" id="ville" name="ville" required 
                           value="<?php echo htmlspecialchars($membre['ville'] ?? ''); ?>"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="cp" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-color);">Code postal *</label>
                    <input type="text" id="cp" name="cp" required 
                           value="<?php echo htmlspecialchars($membre['cp'] ?? ''); ?>"
                           pattern="[0-9]{5}" 
                           maxlength="5"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="adresse" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-color);">Adresse *</label>
                    <textarea id="adresse" name="adresse" required rows="3"
                              style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"><?php echo htmlspecialchars($membre['adresse'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions" style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                    <button type="submit" class="btn-mettre-a-jour" style="border: none; cursor: pointer;">Enregistrer les modifications</button>
                    <a href="<?php echo SITE_URL; ?>/pages/profil.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>

