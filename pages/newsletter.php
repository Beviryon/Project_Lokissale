<?php
/**
 * Page Newsletter
 * Permet aux utilisateurs de s'abonner ou se désabonner à la newsletter
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
requireLogin();

$pageTitle = 'Newsletter - LOKISALLE';
$pageCSS = 'newsletter.css';

$db = getDB();
$id_membre = $_SESSION['membre']['id_membre'];

// Vérifier si l'utilisateur est déjà abonné
$stmt = $db->prepare("SELECT * FROM newsletter WHERE id_membre = ?");
$stmt->execute([$id_membre]);
$abonne = $stmt->fetch();

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <p>&gt;&gt; Newsletter</p>
    </div>
    
    <div class="newsletter-page">
        <h1>Newsletter LOKISALLE</h1>
        
        <?php if ($abonne): ?>
            <!-- Section : Déjà abonné -->
            <div class="newsletter-already-subscribed">
                <p>✓ Vous êtes abonné à notre newsletter</p>
                <p>Vous recevez régulièrement nos dernières offres et actualités.</p>
                <p class="form-help">Date d'inscription : <?php echo formatDate($abonne['date_inscription'], 'd/m/Y à H:i'); ?></p>
                
                <div class="newsletter-actions">
                    <form method="POST" action="<?php echo SITE_URL; ?>/actions/newsletter-unsubscribe.php" style="display: inline;">
                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Êtes-vous sûr de vouloir vous désabonner ?');">
                            Se désabonner
                        </button>
                    </form>
                    <a href="<?php echo SITE_URL; ?>/pages/profil.php" class="btn btn-primary">Retour au profil</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Section : Abonnement -->
            <div class="newsletter-subscribe">
                <p>Restez informé de nos dernières offres et actualités !</p>
                
                <ul>
                    <li>Recevez nos meilleures offres en avant-première</li>
                    <li>Soyez informé des nouvelles salles disponibles</li>
                    <li>Bénéficiez de promotions exclusives</li>
                    <li>Restez à jour avec nos actualités</li>
                </ul>
                
                <div class="newsletter-form">
                    <form method="POST" action="<?php echo SITE_URL; ?>/actions/newsletter-subscribe.php">
                        <div class="form-group">
                            <p><strong>Abonnement à la newsletter</strong></p>
                            <p class="form-help">En vous abonnant, vous acceptez de recevoir nos emails promotionnels.</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">S'abonner à la newsletter</button>
                            <a href="<?php echo SITE_URL; ?>/pages/profil.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>

