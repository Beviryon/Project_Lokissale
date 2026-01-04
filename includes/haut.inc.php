<?php
/**
 * Zone 1 : Haut de page (haut.inc.php)
 * Contient : doctype, head, appel vers feuilles de style, etc.
 * Recommandation du cahier des charges
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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/commun.css">
    
    <!-- CSS pour haut.inc.php (Zone 1 : Header) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/haut.inc.css">
    
    <!-- CSS pour menu.inc.php (Zone 2 : Navigation) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/menu.inc.css">
    
    <!-- CSS pour bas.inc.php (Zone 5 : Footer) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/bas.inc.css">
    
    <!-- CSS spécifique à la page (si défini) -->
    <?php if (isset($pageCSS)): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/<?php echo htmlspecialchars($pageCSS); ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
</head>
<body>
    <!-- Zone 1 : Logo / Bannière / Slogan -->
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <h1><?php echo SITE_NAME; ?></h1>
                        <p class="tagline">Location de salles professionnelles</p>
                    </a>
                </div>
            </div>
        </div>
    </header>