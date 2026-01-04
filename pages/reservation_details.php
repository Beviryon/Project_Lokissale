<?php
/**
 * Page réservation en détail (reservation_details.php)
 * Zone 3 : Informations importantes de la salle
 * Zone 4 : Informations complémentaires
 * Zone 6 : Avis et commentaires
 * Zone 7 : Produits similaires
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

$pageTitle = 'Réservation en détail - LOKISALLE';
$pageCSS = 'reservation_details.css';

// Vérification de l'ID produit (selon cahier des charges, on passe id_produit)
if (!isset($_GET['id_produit']) || !is_numeric($_GET['id_produit'])) {
    redirect('pages/reservation.php', 'Produit introuvable.');
}

$id_produit = (int)$_GET['id_produit'];
$db = getDB();

// Récupération du produit avec ses informations
$stmt = $db->prepare("
    SELECT p.*, 
           s.*,
           pr.code_promo, 
           pr.reduction,
           AVG(a.note) as note_moyenne,
           COUNT(DISTINCT a.id_avis) as nb_avis
    FROM produit p
    JOIN salle s ON p.id_salle = s.id_salle
    LEFT JOIN promotion pr ON p.id_promo = pr.id_promo
    LEFT JOIN avis a ON s.id_salle = a.id_salle
    WHERE p.id_produit = ?
    AND p.etat = 0
    GROUP BY p.id_produit, s.id_salle
");
$stmt->execute([$id_produit]);
$produit = $stmt->fetch();

if (!$produit) {
    redirect('pages/reservation.php', 'Produit introuvable ou non disponible.');
}

$id_salle = $produit['id_salle'];

// Calcul du prix avec réduction et TVA
$prix_ht = $produit['prix'];
if ($produit['reduction']) {
    $prix_ht = calculatePriceWithDiscount($produit['prix'], $produit['reduction']);
}
$tva = $prix_ht * 0.20; // TVA à 20%
$prix_ttc = $prix_ht + $tva;

// Récupération des produits similaires (dates similaires)
$stmt = $db->prepare("
    SELECT p.*, 
           s.titre as salle_titre, 
           s.ville, 
           s.capacite, 
           s.photo,
           pr.code_promo, 
           pr.reduction
    FROM produit p
    JOIN salle s ON p.id_salle = s.id_salle
    LEFT JOIN promotion pr ON p.id_promo = pr.id_promo
    WHERE p.id_produit != ?
    AND p.etat = 0
    AND p.date_arrivee >= NOW()
    AND (
        (p.date_arrivee <= ? AND p.date_depart >= ?) OR
        (p.date_arrivee >= ? AND p.date_arrivee <= ?) OR
        (p.date_depart >= ? AND p.date_depart <= ?) OR
        s.ville = ?
    )
    ORDER BY ABS(DATEDIFF(p.date_arrivee, ?))
    LIMIT 3
");
$stmt->execute([
    $id_produit,
    $produit['date_depart'], $produit['date_arrivee'],
    $produit['date_arrivee'], $produit['date_depart'],
    $produit['date_arrivee'], $produit['date_depart'],
    $produit['ville'],
    $produit['date_arrivee']
]);
$produits_similaires = $stmt->fetchAll();

// Récupération des avis sur la salle (pas le produit)
$stmt = $db->prepare("
    SELECT a.*, m.pseudo, m.nom, m.prenom
    FROM avis a
    JOIN membre m ON a.id_membre = m.id_membre
    WHERE a.id_salle = ?
    ORDER BY a.date DESC
    LIMIT 10
");
$stmt->execute([$id_salle]);
$avis = $stmt->fetchAll();

// Calcul de la note moyenne sur 10 (convertir de 1-5 à 1-10)
$note_moyenne_10 = $produit['note_moyenne'] ? ($produit['note_moyenne'] * 2) : 0;

// Vérifier si l'utilisateur connecté a déjà laissé un avis pour cette salle
$deja_avis = false;
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT id_avis FROM avis WHERE id_salle = ? AND id_membre = ?");
    $stmt->execute([$id_salle, $_SESSION['membre']['id_membre']]);
    $deja_avis = $stmt->fetch() !== false;
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Réservation en détails</p>
    </div>
    
    <!-- Zone 3 : Informations importantes de la salle -->
    <div class="zone-3-salle">
        <div class="salle-header">
            <div class="salle-photo">
                <?php if (!empty($produit['photo'])): ?>
                    <img src="<?php echo UPLOAD_URL . 'salles/' . htmlspecialchars($produit['photo']); ?>" 
                         alt="<?php echo htmlspecialchars($produit['titre']); ?>" 
                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                <?php else: ?>
                    <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" alt="Image non disponible">
                <?php endif; ?>
            </div>
            
            <div class="salle-info-principale">
                <h1 class="salle-titre">
                    <?php echo htmlspecialchars($produit['titre']); ?>
                    <?php if ($produit['note_moyenne']): ?>
                        <span class="note-moyenne">(<?php echo number_format($note_moyenne_10, 0); ?>/10 moyenne sur <?php echo $produit['nb_avis']; ?> avis)</span>
                    <?php endif; ?>
                </h1>
                
                <div class="salle-description">
                    <p><?php echo nl2br(htmlspecialchars($produit['description'])); ?></p>
                </div>
                
                <div class="salle-meta">
                    <p><strong>Capacité:</strong> <?php echo $produit['capacite']; ?></p>
                    <p><strong>Catégorie:</strong> <?php echo ucfirst($produit['categorie']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Zone 4 et Zone 6 : Deux colonnes -->
    <div class="zones-4-6-grid">
        <!-- Zone 4 : Informations complémentaires (colonne gauche) -->
        <div class="zone-4-info-complementaires">
            <h2>Informations complémentaires</h2>
            
            <div class="info-complementaires">
                <p><strong>Pays:</strong> <?php echo htmlspecialchars($produit['pays']); ?></p>
                <p><strong>Ville:</strong> <?php echo htmlspecialchars($produit['ville']); ?></p>
                <p><strong>Adresse:</strong> <?php echo htmlspecialchars($produit['adresse']); ?></p>
                <p><strong>Cp:</strong> <?php echo htmlspecialchars($produit['cp']); ?></p>
                
                <div class="dates-info">
                    <?php 
                    $date_arr = new DateTime($produit['date_arrivee']);
                    $date_dep = new DateTime($produit['date_depart']);
                    ?>
                    <p><strong>Date d'arrivée:</strong> <?php echo $date_arr->format('d/m/Y'); ?> <?php echo $date_arr->format('H'); ?>h</p>
                    <p><strong>Date de départ:</strong> <?php echo $date_dep->format('d/m/Y'); ?> <?php echo $date_dep->format('H'); ?>h</p>
                </div>
                
                <div class="prix-info">
                    <p><strong>Prix:</strong> <?php echo number_format($prix_ht / 100, 0, ',', ' '); ?>€*</p>
                    <p class="note-tva">*Ce prix est hors taxes</p>
                    <p class="prix-ttc-info">Prix TTC (avec TVA 20%): <strong><?php echo number_format($prix_ttc / 100, 0, ',', ' '); ?>€</strong></p>
                </div>
                
                <!-- Plan d'accès (optionnel - Google Maps) -->
                <div class="acces-map">
                    <p><strong>Accès:</strong></p>
                    <!-- Ici on pourrait intégrer Google Maps avec l'adresse -->
                    <div class="map-placeholder">
                        <p><em>Plan d'accès (optionnel)</em></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Zone 6 : Avis (colonne droite) -->
        <div class="zone-6-avis">
            <h2>Avis</h2>
            
            <?php if (!empty($avis)): ?>
                <div class="avis-list">
                    <?php foreach ($avis as $un_avis): ?>
                        <div class="avis-item">
                            <?php 
                            $date_avis = new DateTime($un_avis['date']);
                            ?>
                            <p class="avis-auteur-date">
                                <strong><?php echo htmlspecialchars($un_avis['pseudo']); ?></strong>, 
                                le <?php echo $date_avis->format('d/m/Y'); ?> à <?php echo $date_avis->format('H'); ?>h<?php echo $date_avis->format('i'); ?> 
                                (<?php echo ($un_avis['note'] * 2); ?>/10)
                            </p>
                            <p class="avis-commentaire"><?php echo nl2br(htmlspecialchars($un_avis['commentaire'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isLoggedIn()): ?>
                <?php if ($deja_avis): ?>
                    <div class="message-merci">
                        <p><strong>Merci pour votre contribution</strong></p>
                    </div>
                <?php else: ?>
                    <div class="form-avis">
                        <h3>Ajouter un commentaire</h3>
                        <form method="POST" action="<?php echo SITE_URL; ?>/actions/ajouter-avis.php">
                            <input type="hidden" name="id_salle" value="<?php echo $id_salle; ?>">
                            <input type="hidden" name="id_produit" value="<?php echo $id_produit; ?>">
                            
                            <div class="form-group">
                                <label for="commentaire">Commentaire</label>
                                <textarea id="commentaire" name="commentaire" required minlength="10"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="note">Note</label>
                                <input type="number" id="note" name="note" min="1" max="5" value="5" required>
                                <span class="note-label">(sur 5, sera affichée sur 10)</span>
                            </div>
                            
                            <button type="submit" class="btn-soumettre">Soumettre</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="message-connecter">Il faut être connecté pour pouvoir déposer des commentaires</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bouton Ajouter au panier -->
    <div class="btn-panier-container">
        <?php if (isLoggedIn()): ?>
            <form method="POST" action="<?php echo SITE_URL; ?>/actions/panier.php" class="form-ajouter-panier">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
                <button type="submit" class="btn-ajouter-panier">Ajouter au panier</button>
            </form>
        <?php else: ?>
            <p class="message-inscription">Veuillez-vous inscrire ou vous connecter pour pouvoir effectuer une réservation</p>
        <?php endif; ?>
    </div>
    
    <!-- Zone 7 : Produits similaires -->
    <?php if (!empty($produits_similaires)): ?>
        <div class="zone-7-suggestions">
            <h2>Autres Suggestions /</h2>
            
            <div class="suggestions-grid">
                <?php foreach ($produits_similaires as $similaire): ?>
                    <div class="suggestion-card">
                        <div class="suggestion-photo">
                            <?php if (!empty($similaire['photo'])): ?>
                                <img src="<?php echo UPLOAD_URL . 'salles/' . htmlspecialchars($similaire['photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($similaire['salle_titre']); ?>" 
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                            <?php else: ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" alt="Image non disponible">
                            <?php endif; ?>
                        </div>
                        
                        <div class="suggestion-details">
                            <?php 
                            $date_arr_sim = new DateTime($similaire['date_arrivee']);
                            $date_dep_sim = new DateTime($similaire['date_depart']);
                            $mois_fr = ['jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jui', 'aoû', 'sep', 'oct', 'nov', 'déc'];
                            $mois_arr_sim = (int)$date_arr_sim->format('n') - 1;
                            $mois_dep_sim = (int)$date_dep_sim->format('n') - 1;
                            
                            $prix_sim_ht = $similaire['prix'];
                            if ($similaire['reduction']) {
                                $prix_sim_ht = calculatePriceWithDiscount($similaire['prix'], $similaire['reduction']);
                            }
                            $prix_sim_ttc = $prix_sim_ht * 1.20;
                            ?>
                            <p class="suggestion-date-ville">
                                Du <?php echo $date_arr_sim->format('j'); ?> <?php echo $mois_fr[$mois_arr_sim]; ?> au 
                                <?php echo $date_dep_sim->format('j'); ?> <?php echo $mois_fr[$mois_dep_sim]; ?> <?php echo $date_dep_sim->format('Y'); ?> – 
                                <?php echo strtoupper(htmlspecialchars($similaire['ville'])); ?>
                            </p>
                            <p class="suggestion-prix"><?php echo number_format($prix_sim_ttc / 100, 0, ',', ' '); ?> euros* pour <?php echo $similaire['capacite']; ?> personnes</p>
                            <a href="<?php echo SITE_URL; ?>/pages/reservation_details.php?id_produit=<?php echo $similaire['id_produit']; ?>" class="suggestion-link">
                                Voir la fiche de la salle <?php echo htmlspecialchars($similaire['salle_titre']); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/bas.inc.php'; ?>