<?php
/**
 * Page d'accueil du site LOKISALLE
 * Conforme au cahier des charges 7.101
 * Zone 4 : Affiche les 3 dernières offres (produits réservables)
 */

require_once 'includes/config.php'; 
require_once 'includes/functions.php';


$pageTitle = 'Accueil - LOKISALLE';
$pageCSS = 'index.css';  
$pageJS = 'index.js'; 

// Récupération des 3 dernières offres (produits réservables)
// Conforme au cahier des charges 7.101 - Zone 4
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
    WHERE p.etat = 1 
    AND p.date_arrivee >= NOW()
    ORDER BY p.id_produit DESC
    LIMIT 3
");
$dernieres_offres = $stmt->fetchAll();

// Inclusion des fichiers selon recommandations du cahier des charges
include 'includes/haut.inc.php';  
include 'includes/menu.inc.php'; 
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Accueil</p>
    </div>
    
    <!-- Zone 3 et Zone 4 : Deux colonnes -->
    <div class="home-content-grid">
        <!-- Zone 3 : Texte de présentation (colonne de gauche) -->
        <div class="zone-3-presentation">
            <div class="card">
                <h2 class="card-title">Bienvenue sur LOKISALLE</h2>
                <p>
                    LOKISALLE est votre partenaire de confiance pour la location de salles professionnelles. 
                    Que vous organisiez une réunion, un séminaire, une conférence ou une formation, 
                    nous avons la salle qu'il vous faut.
                </p>
                <p>
                    Nous proposons des salles modernes et équipées dans les plus grandes villes de France : 
                    <strong>Paris</strong>, <strong>Lyon</strong> et <strong>Marseille</strong>.
                </p>
                <p>
                    Notre mission est de vous offrir des espaces adaptés à vos besoins, que ce soit pour 
                    une réunion d'équipe, une formation professionnelle, un séminaire ou une conférence. 
                    Toutes nos salles sont situées dans des emplacements stratégiques et sont équipées 
                    du matériel nécessaire pour garantir le succès de vos événements.
                </p>
                <p>
                    Avec LOKISALLE, réservez en toute simplicité et bénéficiez de tarifs transparents 
                    et compétitifs. Consultez nos offres disponibles et trouvez la salle idéale pour 
                    votre prochain événement.
                </p>
            </div>
        </div>
        
        <!-- Zone 4 : Nos 3 dernières offres (colonne de droite) -->
        <div class="zone-4-offres">
            <div class="card">
                <h2 class="card-title">Nos 3 dernières offres</h2>
                
                <?php if (empty($dernieres_offres)): ?>
                    <p class="text-center">Aucune offre pour le moment</p>
                <?php else: ?>
                    <div class="offres-list">
                        <?php foreach ($dernieres_offres as $offre): ?>
                            <div class="offre-item">
                                <div class="offre-photo">
                                    <?php if (!empty($offre['photo'])): ?>
                                        <img src="<?php echo UPLOAD_URL . 'salles/' . htmlspecialchars($offre['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($offre['salle_titre']); ?>" 
                                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                                    <?php else: ?>
                                        <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" alt="Image non disponible">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="offre-details">
                                    <?php 
                                    // Format de date selon la maquette : "6 déc 2015"
                                    $date_arrivee = new DateTime($offre['date_arrivee']);
                                    $date_depart = new DateTime($offre['date_depart']);
                                    $mois_fr = ['jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jui', 'aoû', 'sep', 'oct', 'nov', 'déc'];
                                    $mois_arr = (int)$date_arrivee->format('n') - 1;
                                    $mois_dep = (int)$date_depart->format('n') - 1;
                                    ?>
                                    <p><strong>Du <?php echo $date_arrivee->format('j') . ' ' . $mois_fr[$mois_arr] . ' ' . $date_arrivee->format('Y'); ?> au <?php echo $date_depart->format('j') . ' ' . $mois_fr[$mois_dep] . ' ' . $date_depart->format('Y'); ?></strong></p>
                                    <p><strong><?php echo strtoupper(htmlspecialchars($offre['ville'])); ?></strong></p>
                                    
                                    <?php 
                                    $prix_ht = $offre['prix'];
                                    if ($offre['reduction']) {
                                        $prix_ht = calculatePriceWithDiscount($offre['prix'], $offre['reduction']);
                                    }
                                    $prix_ttc = $prix_ht * 1.20; // TVA 20%
                                    ?>
                                    <p><strong><?php echo number_format($prix_ttc / 100, 0, ',', ' '); ?> euros *</strong> pour <?php echo $offre['capacite']; ?> personnes</p>
                                    
                                    <div class="offre-actions">
                                        <a href="<?php echo SITE_URL; ?>/pages/reservation_details.php?id_produit=<?php echo $offre['id_produit']; ?>" 
                                           class="offre-link">
                                            &gt; Voir la fiche détaillée
                                        </a>
                                        
                                        <a href="<?php echo SITE_URL; ?>/auth/connexion.php" class="offre-link">
                                            &gt; Connectez vous pour l'ajouter au panier
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/bas.inc.php'; // Zone 5 : footer et scripts ?>