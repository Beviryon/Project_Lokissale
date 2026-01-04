<?php
/**
 * Zone 2 : Menu de navigation (menu.inc.php)
 * Contient tous les liens du menu
 * Recommandation du cahier des charges
 */
?>
<!-- Zone 2 : Navigation principale -->
<nav class="main-nav">
    <div class="container">
        <ul class="nav-list">
            <li><a href="<?php echo SITE_URL; ?>/index.php">Accueil</a></li>
            <li class="nav-separator">|</li>
            <li><a href="<?php echo SITE_URL; ?>/pages/reservation.php">Réservation</a></li>
            <li class="nav-separator">|</li>
            <li><a href="<?php echo SITE_URL; ?>/pages/recherche.php">Recherche</a></li>
            <?php if (isLoggedIn()): ?>
                <li class="nav-separator">|</li>
                <li><a href="<?php echo SITE_URL; ?>/actions/panier.php">Panier</a></li>
                <li class="nav-separator">|</li>
                <li><a href="<?php echo SITE_URL; ?>/pages/profil.php">Mon Profil</a></li>
                <?php if (isAdmin()): ?>
                    <li class="nav-separator">|</li>
                    <li><a href="<?php echo SITE_URL; ?>/admin/index.php">Administration</a></li>
                <?php endif; ?>
                <li class="nav-separator">|</li>
                <li><a href="<?php echo SITE_URL; ?>/auth/deconnexion.php">Se déconnecter</a></li>
            <?php else: ?>
                <li class="nav-separator">|</li>
                <li><a href="<?php echo SITE_URL; ?>/auth/connexion.php">Se connecter</a></li>
                <li class="nav-separator">|</li>
                <li><a href="<?php echo SITE_URL; ?>/auth/inscription.php">Créer un nouveau compte</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Messages flash -->
<?php 
$message = getFlashMessage();
if (!empty($message)): 
?>
    <div class="flash-message">
        <div class="container">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Contenu principal -->
<main class="main-content">