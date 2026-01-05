<?php
/**
 * Page de recherche (recherche.php)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Recherche - LOKISALLE';
$pageCSS = 'recherche.css';

$db = getDB();
$produits = [];
$has_search = false;
$nb_resultats = 0;

// Mois et année en cours (par défaut)
$mois_actuel = (int)date('n'); // 1-12
$annee_actuelle = (int)date('Y');

// Traitement de la recherche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recherche'])) {
    $has_search = true;
    
    // Récupération des 3 valeurs : mois, année, mots-clés
    $mois = (int)($_POST['mois'] ?? $mois_actuel);
    $annee = (int)($_POST['annee'] ?? $annee_actuelle);
    $mots_cles = cleanInput($_POST['mots_cles'] ?? '');
    
    // Conversion en format américain (ANNEE/MOIS/JOUR)
    // On prend le premier jour du mois pour la recherche
    $date_recherche = sprintf('%04d-%02d-01', $annee, $mois);
    
    // Construction de la requête pour les produits réservables
    $sql = "SELECT p.*, 
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
            AND p.date_arrivee >= ?";
    
    $params = [$date_recherche];
    
    // Filtrage par mots-clés (ville ou titre de la salle)
    if (!empty($mots_cles)) {
        $sql .= " AND (s.ville LIKE ? OR s.titre LIKE ?)";
        $mots_cles_like = '%' . $mots_cles . '%';
        $params[] = $mots_cles_like;
        $params[] = $mots_cles_like;
    }
    
    $sql .= " ORDER BY p.date_arrivee ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $produits = $stmt->fetchAll();
    $nb_resultats = count($produits);
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Recherche</p>
    </div>
    
    <!-- Zone 4 : Formulaire de recherche -->
    <div class="search-form-container">
        <h2 class="search-form-title">Recherche d'une location de salle pour réservation.</h2>
        <form method="POST" action="" class="search-form">
            <div class="search-fields">
                <div class="search-field-group">
                    <label for="mois">A la date du</label>
                    <div class="date-fields">
                        <select id="mois" name="mois" required>
                            <?php 
                            $mois_noms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                                         'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                            $mois_selected = isset($_POST['mois']) ? (int)$_POST['mois'] : $mois_actuel;
                            for ($i = 1; $i <= 12; $i++): 
                            ?>
                                <option value="<?php echo $i; ?>" <?php echo ($i == $mois_selected) ? 'selected' : ''; ?>>
                                    <?php echo $mois_noms[$i-1]; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        
                        <select id="annee" name="annee" required>
                            <?php 
                            $annee_selected = isset($_POST['annee']) ? (int)$_POST['annee'] : $annee_actuelle;
                            // Années de 2015 à 2030
                            for ($a = 2015; $a <= 2030; $a++): 
                            ?>
                                <option value="<?php echo $a; ?>" <?php echo ($a == $annee_selected) ? 'selected' : ''; ?>>
                                    <?php echo $a; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="search-field-group">
                    <label for="mots_cles">Par mots clés</label>
                    <input type="text" id="mots_cles" name="mots_cles" 
                           placeholder="Ex: Paris" 
                           value="<?php echo htmlspecialchars($_POST['mots_cles'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" name="recherche" class="btn-recherche">Recherche</button>
        </form>
    </div>
    
    <!-- Zone 3 : Résultats de recherche -->
    <?php if ($has_search): ?>
        <div class="results-container">
            <h2 class="results-title">Resultats de votre recherche</h2>
            <p class="results-count">Nombre de résultat(s): <?php echo $nb_resultats; ?></p>
            
            <?php if (empty($produits)): ?>
                <p class="text-center">Aucune offre ne correspond à vos critères de recherche.</p>
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
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/bas.inc.php'; ?>