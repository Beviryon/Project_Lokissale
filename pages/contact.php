<?php
/**
 * Page Contact (contact.php)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Contact - LOKISALLE';
$pageCSS = 'contact.css';

// Récupération des messages de session
$message = '';
$error = '';
if (isset($_SESSION['contact_success'])) {
    $message = $_SESSION['contact_success'];
    unset($_SESSION['contact_success']);
}
if (isset($_SESSION['contact_errors'])) {
    $error = implode(' ', $_SESSION['contact_errors']);
    unset($_SESSION['contact_errors']);
}

// Récupération des données du formulaire en cas d'erreur
$form_data = $_SESSION['contact_form_data'] ?? [];
unset($_SESSION['contact_form_data']);

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Contact</p>
    </div>
    
    <!-- Zone 3 : Formulaire de contact -->
    <div class="contact-form-container">
        <?php if ($message): ?>
            <div class="success-message">
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo SITE_URL; ?>/actions/contact.php" class="contact-form">
            <div class="form-group">
                <label for="sujet">Sujet</label>
                <input type="text" id="sujet" name="sujet" 
                       value="<?php echo htmlspecialchars($form_data['sujet'] ?? ''); ?>"
                       required autofocus>
            </div>
            
            <?php if (!isLoggedIn()): ?>
                <!-- Champ expéditeur seulement pour les visiteurs -->
                <div class="form-group">
                    <label for="expediteur">Expéditeur</label>
                    <input type="email" id="expediteur" name="expediteur" 
                           value="<?php echo htmlspecialchars($form_data['expediteur'] ?? ''); ?>"
                           required>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="10" required><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn-envoi">Envoi</button>
        </form>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>