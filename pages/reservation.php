<?php
/**
 * Page Réservation (reservation.php)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Réservation - LOKISALLE';
$pageCSS = 'reservation.css';

$db = getDB();

// Configuration de la pagination
$elements_par_page = 10; // 10 offres par page (2 lignes de 5)
$page_actuelle = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page_actuelle = max(1, $page_actuelle); // Minimum page 1
$offset = ($page_actuelle - 1) * $elements_par_page;

// Requête pour compter le total de produits disponibles
$sql_count = "SELECT COUNT(DISTINCT p.id_produit) as total
              FROM produit p
              JOIN salle s ON p.id_salle = s.id_salle
              WHERE p.etat = 0 
              AND p.date_arrivee >= NOW()";

$stmt_count = $db->query($sql_count);
$total_produits = (int)$stmt_count->fetch()['total'];
$total_pages = ceil($total_produits / $elements_par_page);

// Récupération des produits disponibles (réservables) avec pagination
// Produits avec état = 0 (disponible) et date_arrivee >= NOW()
$sql = "SELECT p.*, 
               s.titre as salle_titre,
               s.ville,
               s.cp,
               s.capacite,
               s.categorie,
               s.photo,
               pr.code_promo,
               pr.reduction,
               AVG(a.note) as note_moyenne,
               COUNT(DISTINCT a.id_avis) as nb_avis
        FROM produit p
        JOIN salle s ON p.id_salle = s.id_salle
        LEFT JOIN promotion pr ON p.id_promo = pr.id_promo
        LEFT JOIN avis a ON s.id_salle = a.id_salle
        WHERE p.etat = 0 
        AND p.date_arrivee >= NOW()
        GROUP BY p.id_produit
        ORDER BY p.date_arrivee ASC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
$stmt->bindValue(':limit', $elements_par_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$produits = $stmt->fetchAll();

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Réservation</p>
    </div>
    
    <!-- Zone 3 : Toutes nos offres -->
    <div class="offres-container">
        <h2 class="offres-title">Toutes nos offres</h2>
        
        <?php if (empty($produits)): ?>
            <p class="text-center">Aucune offre pour le moment</p>
        <?php else: ?>
            <div class="offres-grid">
                <?php foreach ($produits as $produit): ?>
                    <div class="offre-card">
                        <!-- Photo -->
                        <div class="offre-photo">
                            <?php if (!empty($produit['photo'])): ?>
                                <img src="<?php echo UPLOAD_URL . 'salles/' . htmlspecialchars($produit['photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($produit['salle_titre']); ?>" 
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                            <?php else: ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" alt="Image non disponible">
                            <?php endif; ?>
                        </div>
                        
                        <!-- Détails de l'offre -->
                        <div class="offre-details">
                            <?php 
                            // Format de date selon la maquette : "6 déc 2015"
                            $date_arrivee = new DateTime($produit['date_arrivee']);
                            $date_depart = new DateTime($produit['date_depart']);
                            $mois_fr = ['jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jui', 'aoû', 'sep', 'oct', 'nov', 'déc'];
                            $mois_arr = (int)$date_arrivee->format('n') - 1;
                            $mois_dep = (int)$date_depart->format('n') - 1;
                            ?>
                            <p class="offre-date-ville"><strong>Du <?php echo $date_arrivee->format('j') . ' ' . $mois_fr[$mois_arr] . ' ' . $date_arrivee->format('Y'); ?> au <?php echo $date_depart->format('j') . ' ' . $mois_fr[$mois_dep] . ' ' . $date_depart->format('Y'); ?> - <?php echo strtoupper(htmlspecialchars($produit['ville'])); ?></strong></p>
                            
                            <?php 
                            $prix_ht = $produit['prix'];
                            if ($produit['reduction']) {
                                $prix_ht = calculatePriceWithDiscount($produit['prix'], $produit['reduction']);
                            }
                            $prix_ttc = $prix_ht * 1.20; // TVA 20%
                            ?>
                            <p class="offre-prix"><strong><?php echo number_format($prix_ttc / 100, 0, ',', ' '); ?> euros *</strong> pour <?php echo $produit['capacite']; ?> personnes</p>
                            
                            <!-- Actions -->
                            <div class="offre-actions">
                                <a href="<?php echo SITE_URL; ?>/pages/reservation_details.php?id_produit=<?php echo $produit['id_produit']; ?>" class="offre-link">
                                    &gt; Voir la fiche détaillée
                                </a>
                                
                                <?php if (isLoggedIn()): ?>
                                    <form method="POST" action="<?php echo SITE_URL; ?>/actions/panier.php" class="offre-form">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
                                        <button type="submit" class="btn-ajouter-panier">
                                            Ajouter au panier
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>/auth/connexion.php" class="offre-link">
                                        &gt; Connectez vous pour l'ajouter au panier
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Page <?php echo $page_actuelle; ?> sur <?php echo $total_pages; ?> 
                        (<?php echo $total_produits; ?> offre<?php echo $total_produits > 1 ? 's' : ''; ?> au total)
                    </div>
                    <div class="pagination">
                        <?php if ($page_actuelle > 1): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/reservation.php?page=<?php echo $page_actuelle - 1; ?>" class="prev">‹ Précédent</a>
                        <?php else: ?>
                            <span class="disabled">‹ Précédent</span>
                        <?php endif; ?>
                        
                        <?php
                        // Affichage des numéros de pages
                        $start_page = max(1, $page_actuelle - 2);
                        $end_page = min($total_pages, $page_actuelle + 2);
                        
                        // Première page
                        if ($start_page > 1): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/reservation.php?page=1">1</a>
                            <?php if ($start_page > 2): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php
                        // Pages autour de la page actuelle
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page_actuelle): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/pages/reservation.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php
                        // Dernière page
                        if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span>...</span>
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>/pages/reservation.php?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page_actuelle < $total_pages): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/reservation.php?page=<?php echo $page_actuelle + 1; ?>" class="next">Suivant ›</a>
                        <?php else: ?>
                            <span class="disabled">Suivant ›</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>