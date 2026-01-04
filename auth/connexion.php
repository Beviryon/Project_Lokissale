<?php
/**
 * Page de connexion (connexion.php)
 * Conforme au cahier des charges 7.105
 * Zone 3 : Interface de connexion
 * Zone 4 : Lien vers inscription
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Connexion - LOKISALLE';
$pageCSS = 'connexion.css';

$errors = [];

// Si l'utilisateur est déjà connecté, redirection vers profil
if (isLoggedIn()) {
    redirect('pages/profil.php', 'Vous êtes déjà connecté.');
}

// Récupération du pseudo depuis le cookie "Se souvenir de moi"
$pseudo_cookie = $_COOKIE['lokisalle_pseudo'] ?? '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = cleanInput($_POST['pseudo'] ?? '');
    $mdp = $_POST['mdp'] ?? '';
    $se_souvenir = isset($_POST['se_souvenir']) && $_POST['se_souvenir'] == '1';
    
    if (empty($pseudo) || empty($mdp)) {
        $errors[] = "Veuillez remplir tous les champs.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM membre WHERE pseudo = ?");
        $stmt->execute([$pseudo]);
        $membre = $stmt->fetch();
        
        if ($membre && password_verify($mdp, $membre['mdp'])) {
            // Connexion réussie
            $_SESSION['membre'] = [
                'id_membre' => $membre['id_membre'],
                'pseudo' => $membre['pseudo'],
                'nom' => $membre['nom'],
                'prenom' => $membre['prenom'],
                'email' => $membre['email'],
                'statut' => $membre['statut']
            ];
            
            // Gestion du cookie "Se souvenir de moi"
            if ($se_souvenir) {
                // Sauvegarder le pseudo dans un cookie (durée : 1 an)
                setcookie('lokisalle_pseudo', $pseudo, time() + (365 * 24 * 60 * 60), '/');
            } else {
                // Supprimer le cookie s'il existe
                if (isset($_COOKIE['lokisalle_pseudo'])) {
                    setcookie('lokisalle_pseudo', '', time() - 3600, '/');
                }
            }
            
            // Redirection vers profil.php 
            redirect('pages/profil.php', 'Connexion réussie ! Bienvenue ' . $membre['pseudo'] . ' !');
        } else {
            $errors[] = "Pseudo ou mot de passe incorrect.";
        }
    }
} else {
    // Si pas de POST, utiliser le pseudo du cookie si disponible
    $pseudo_cookie = $_COOKIE['lokisalle_pseudo'] ?? '';
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Connexion</p>
    </div>
    
    <!-- Zone 3 et Zone 4 : Deux colonnes -->
    <div class="connexion-layout">
        <!-- Zone 3 : Interface de connexion (colonne gauche) -->
        <div class="zone-3-connexion">
            <h2>Déjà membre ?</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="connexion-form">
                <div class="form-group">
                    <label for="pseudo">Pseudo</label>
                    <input type="text" id="pseudo" name="pseudo" 
                           value="<?php echo htmlspecialchars($_POST['pseudo'] ?? $pseudo_cookie); ?>"
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="mdp">Mot de passe</label>
                    <input type="password" id="mdp" name="mdp" required>
                    <p class="forgot-password">
                        <a href="<?php echo SITE_URL; ?>/auth/motdepasseperdu.php">Mot de passe oublié ?</a>
                    </p>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="se_souvenir" name="se_souvenir" value="1"
                           <?php echo (isset($_COOKIE['lokisalle_pseudo']) && !empty($_COOKIE['lokisalle_pseudo'])) ? 'checked' : ''; ?>>
                    <label for="se_souvenir">Se souvenir de moi</label>
                </div>
                
                <button type="submit" class="btn-connexion">connexion</button>
            </form>
        </div>
        
        <!-- Zone 4 : Lien vers inscription (colonne droite) -->
        <div class="zone-4-inscription">
            <h2>Pas encore membre?</h2>
            <p>
                <a href="<?php echo SITE_URL; ?>/auth/inscription.php" class="link-inscription">Inscrivez-vous</a>
            </p>
        </div>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>