<?php
/**
 * Page d'accueil du site LOKISALLE
 * Design moderne et professionnel
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Accueil - LOKISALLE';
$pageCSS = 'index.css';  
$pageJS = 'index.js'; 

// Récupération des 3 dernières offres (produits réservables)
$db = getDB();
$stmt = $db->query("
    SELECT p.*, 
           s.titre as salle_titre,
           s.ville,
           s.cp,
           s.capacite,
           s.photo,
           pr.code_promo,
           pr.reduction
    FROM produit p
    JOIN salle s ON p.id_salle = s.id_salle
    LEFT JOIN promotion pr ON p.id_promo = pr.id_promo
    WHERE p.etat = 0 
    AND p.date_arrivee >= NOW()
    ORDER BY p.id_produit DESC
    LIMIT 3
");
$dernieres_offres = $stmt->fetchAll();

// Inclusion des fichiers
include 'includes/haut.inc.php';  
include 'includes/menu.inc.php'; 
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Trouvez la salle idéale pour votre événement</h1>
            <p class="hero-subtitle">Des espaces professionnels modernes dans les plus grandes villes de France</p>
            <div class="hero-actions">
                <a href="<?php echo SITE_URL; ?>/pages/reservation.php" class="btn btn-primary btn-large">Découvrir nos salles</a>
                <a href="<?php echo SITE_URL; ?>/pages/recherche.php" class="btn btn-secondary btn-large">Rechercher une salle</a>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <!-- Section Présentation -->
    <section class="intro-section">
        <div class="intro-content">
            <h2 class="section-title">Bienvenue sur LOKISALLE</h2>
            <p class="intro-text">
                LOKISALLE est votre partenaire de confiance pour la location de salles professionnelles. 
                Que vous organisiez une réunion, un séminaire, une conférence ou une formation, 
                nous avons la salle qu'il vous faut.
            </p>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon"></div>
                    <h3>Emplacements stratégiques</h3>
                    <p>Paris, Lyon, Marseille</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"></div>
                    <h3>Salles modernes</h3>
                    <p>Équipées et adaptées</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"></div>
                    <h3>Tarifs transparents</h3>
                    <p>Meilleurs prix garantis</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Dernières Offres -->
    <section class="offres-section">
        <div class="section-header">
            <h2 class="section-title">Nos dernières offres</h2>
            <p class="section-subtitle">Découvrez nos salles disponibles dès maintenant</p>
        </div>
        
        <?php if (empty($dernieres_offres)): ?>
            <div class="empty-state">
                <p>Aucune offre disponible pour le moment</p>
                <a href="<?php echo SITE_URL; ?>/pages/reservation.php" class="btn btn-primary">Voir toutes les salles</a>
            </div>
        <?php else: ?>
            <div class="offres-grid">
                <?php foreach ($dernieres_offres as $offre): ?>
                    <article class="offre-card">
                        <div class="offre-image">
                            <?php if (!empty($offre['photo'])): ?>
                                <img src="<?php echo UPLOAD_URL . 'salles/' . htmlspecialchars($offre['photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($offre['salle_titre']); ?>" 
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                            <?php else: ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" alt="Image non disponible">
                            <?php endif; ?>
                            <?php if ($offre['reduction']): ?>
                                <span class="badge-promo">-<?php echo $offre['reduction']; ?>%</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="offre-content">
                            <div class="offre-header">
                                <h3 class="offre-title"><?php echo htmlspecialchars($offre['salle_titre']); ?></h3>
                                <span class="offre-ville"><?php echo strtoupper(htmlspecialchars($offre['ville'])); ?></span>
                            </div>
                            
                            <div class="offre-info">
                                <?php 
                                $date_arrivee = new DateTime($offre['date_arrivee']);
                                $date_depart = new DateTime($offre['date_depart']);
                                $mois_fr = ['jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jui', 'aoû', 'sep', 'oct', 'nov', 'déc'];
                                $mois_arr = (int)$date_arrivee->format('n') - 1;
                                $mois_dep = (int)$date_depart->format('n') - 1;
                                ?>
                                <div class="info-item">
                                    <span class="info-icon">📅</span>
                                    <span class="info-text">
                                        <?php echo $date_arrivee->format('j') . ' ' . $mois_fr[$mois_arr] . ' - ' . $date_depart->format('j') . ' ' . $mois_fr[$mois_dep] . ' ' . $date_depart->format('Y'); ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-icon">👥</span>
                                    <span class="info-text"><?php echo $offre['capacite']; ?> personnes</span>
                                </div>
                            </div>
                            
                            <div class="offre-footer">
                                <?php 
                                $prix_ht = $offre['prix'];
                                if ($offre['reduction']) {
                                    $prix_ht = calculatePriceWithDiscount($offre['prix'], $offre['reduction']);
                                }
                                $prix_ttc = $prix_ht * 1.20; // TVA 20%
                                ?>
                                <div class="offre-prix">
                                    <span class="prix-amount"><?php echo number_format($prix_ttc / 100, 0, ',', ' '); ?> €</span>
                                    <span class="prix-detail">TTC</span>
                                </div>
                                <div class="offre-actions">
                                    <a href="<?php echo SITE_URL; ?>/pages/reservation_details.php?id_produit=<?php echo $offre['id_produit']; ?>" 
                                       class="btn btn-outline">
                                        Voir les détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <div class="section-footer">
                <a href="<?php echo SITE_URL; ?>/pages/reservation.php" class="btn btn-secondary">
                    Voir toutes les offres →
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'includes/bas.inc.php'; ?>