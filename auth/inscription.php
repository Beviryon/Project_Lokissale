<?php
/**
 * Page d'inscription
 * Permet aux visiteurs de créer un compte membre
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Inscription - LOKISALLE';
$pageCSS = 'inscription.css';

$errors = [];
$success = false;

// Si l'utilisateur est déjà connecté, redirection
if (isLoggedIn()) {
    redirect('index.php', 'Vous êtes déjà connecté.');
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
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
    
    if (empty($nom) || strlen($nom) < 2) {
        $errors[] = "Le nom est requis (minimum 2 caractères).";
    }
    
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors[] = "Le prénom est requis (minimum 2 caractères).";
    }
    
    if (!isValidEmail($email)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    
    if (!in_array($sexe, ['m', 'f'])) {
        $errors[] = "Le sexe doit être 'm' ou 'f'.";
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
    
    // Vérification de l'unicité du pseudo et de l'email
    if (empty($errors)) {
        $db = getDB();
        
        // Vérifier le pseudo
        $stmt = $db->prepare("SELECT id_membre FROM membre WHERE pseudo = ?");
        $stmt->execute([$pseudo]);
        if ($stmt->fetch()) {
            $errors[] = "Ce pseudo est déjà utilisé.";
        }
        
        // Vérifier l'email
        $stmt = $db->prepare("SELECT id_membre FROM membre WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }
    
    // Insertion en base de données
    if (empty($errors)) {
        $db = getDB();
        $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
        
        try {
            $stmt = $db->prepare("
                INSERT INTO membre (pseudo, mdp, nom, prenom, email, sexe, ville, cp, adresse, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            $stmt->execute([$pseudo, $mdp_hash, $nom, $prenom, $email, $sexe, $ville, $cp, $adresse]);
            
            $success = true;
            redirect('auth/connexion.php', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'inscription. Veuillez réessayer.";
        }
    }
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <div class="card inscription-container">
        <h2 class="card-title">Créer un compte</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="pseudo">Pseudo *</label>
                <input type="text" id="pseudo" name="pseudo" required 
                       value="<?php echo htmlspecialchars($_POST['pseudo'] ?? ''); ?>"
                       minlength="3" maxlength="15">
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
                <input type="text" id="cp" name="cp" required 
                       value="<?php echo htmlspecialchars($_POST['cp'] ?? ''); ?>"
                       pattern="[0-9]{5}" maxlength="5">
            </div>
            
            <div class="form-group">
                <label for="adresse">Adresse *</label>
                <input type="text" id="adresse" name="adresse" required 
                       value="<?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </form>
        
        <p class="inscription-footer">
            Déjà un compte ? <a href="<?php echo SITE_URL; ?>/auth/connexion.php">Connectez-vous</a>
        </p>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>