<?php
/**
 * En-tête commun à toutes les pages
 */
if (!isset($pageTitle)) {
    $pageTitle = 'LOKISALLE - Location de salles';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LOKISALLE - Location de salles pour réunions, conférences et séminaires à Paris, Lyon et Marseille">
    <meta name="keywords" content="location salle, réunion, conférence, séminaire, Paris, Lyon, Marseille">
    <meta name="author" content="LOKISALLE">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- CSS commun (toujours chargé) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/common.css">
    
    <!-- CSS header -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/header.css">
    
    <!-- CSS spécifique à la page (si défini) -->
    <?php if (isset($pageCSS)): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/<?php echo htmlspecialchars($pageCSS); ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
</head>
<body>
    <!-- En-tête du site -->
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <!-- Logo et nom du site -->
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <h1><?php echo SITE_NAME; ?></h1>
                        <p class="tagline">Location de salles professionnelles</p>
                    </a>
                </div>
                
                <!-- Navigation principale -->
                <nav class="main-nav">
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
                </nav>
            </div>
        </div>
    </header>
    
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