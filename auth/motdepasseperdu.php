<?php
/**
 * Page mot de passe oublié (motdepasseperdu.php)
 * Conforme au cahier des charges 7.106
 * Zone 3 : Formulaire de réinitialisation de mot de passe
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Mot de passe oublié - LOKISALLE';
$pageCSS = 'motdepasseperdu.css';

$message = '';
$error = '';

// Si l'utilisateur est déjà connecté, redirection
if (isLoggedIn()) {
    redirect('pages/profil.php', 'Vous êtes déjà connecté.');
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez saisir une adresse email valide.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id_membre, pseudo, email FROM membre WHERE email = ?");
        $stmt->execute([$email]);
        $membre = $stmt->fetch();
        
        if ($membre) {
            // Générer un nouveau mot de passe aléatoire
            $nouveau_mdp = bin2hex(random_bytes(8)); // 16 caractères aléatoires
            $mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
            
            // Mettre à jour le mot de passe dans la base de données
            try {
                $stmt = $db->prepare("UPDATE membre SET mdp = ? WHERE id_membre = ?");
                $stmt->execute([$mdp_hash, $membre['id_membre']]);
                
                // Préparer l'email
                $sujet = 'Réinitialisation de votre mot de passe - ' . SITE_NAME;
                $message_email = "Bonjour " . htmlspecialchars($membre['pseudo']) . ",\n\n";
                $message_email .= "Vous avez demandé la réinitialisation de votre mot de passe.\n\n";
                $message_email .= "Votre nouveau mot de passe est : " . $nouveau_mdp . "\n\n";
                $message_email .= "Nous vous recommandons de changer ce mot de passe après votre prochaine connexion.\n\n";
                $message_email .= "Cordialement,\n";
                $message_email .= "L'équipe " . SITE_NAME;
                
                // Envoyer l'email
                $headers = "From: " . SITE_EMAIL . "\r\n";
                $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                if (mail($membre['email'], $sujet, $message_email, $headers)) {
                    $message = "Un email contenant votre nouveau mot de passe a été envoyé à l'adresse " . htmlspecialchars($email) . ".";
                } else {
                    // Si l'envoi d'email échoue, afficher le mot de passe (développement uniquement)
                    // En production, ne jamais afficher le mot de passe
                    $error = "Erreur lors de l'envoi de l'email. Veuillez contacter l'administrateur.";
                    // En développement, on peut logger l'erreur
                    error_log("Erreur envoi email mot de passe pour: " . $email);
                }
            } catch (PDOException $e) {
                $error = "Une erreur s'est produite. Veuillez réessayer plus tard.";
                error_log("Erreur réinitialisation mot de passe: " . $e->getMessage());
            }
        } else {
            // Ne pas révéler si l'email existe ou non (sécurité)
            $message = "Si cette adresse email existe dans notre base de données, un email contenant un nouveau mot de passe vous sera envoyé.";
        }
    }
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Mot de passe oublié</p>
    </div>
    
    <!-- Zone 3 : Formulaire de réinitialisation -->
    <div class="motdepasse-form-container">
        <div class="motdepasse-form-box">
            <p class="instruction-text">
                Afin de pouvoir réinitialiser votre mot de passe, vous devez nous fournir votre adresse email :
            </p>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="success-message">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="motdepasse-form">
                <div class="form-group">
                    <input type="email" id="email" name="email" 
                           placeholder="Votre adresse email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required autofocus>
                </div>
                
                <button type="submit" class="btn-valider">Valider</button>
            </form>
            
            <p class="retour-connexion">
                <a href="<?php echo SITE_URL; ?>/auth/connexion.php">&larr; Retour à la page de connexion</a>
            </p>
        </div>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>