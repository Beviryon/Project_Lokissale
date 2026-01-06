<?php
/**
 * Gestion des commandes (gestion_commandes.php)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestion des commandes';
$pageCSS = 'admin.css';
$db = getDB();

// Récupération du chiffre d'affaires total
$stmt = $db->query("SELECT SUM(montant) as ca_total FROM commande WHERE statut = 'validee'");
$ca_total = $stmt->fetchColumn() ?? 0;

// Récupération de toutes les commandes
$stmt = $db->query("
    SELECT c.*, 
           m.pseudo,
           m.nom,
           m.prenom,
           m.email,
           COUNT(dc.id_details_commande) as nb_produits
    FROM commande c
    JOIN membre m ON c.id_membre = m.id_membre
    LEFT JOIN details_commande dc ON c.id_commande = dc.id_commande
    GROUP BY c.id_commande
    ORDER BY c.date DESC
");
$commandes = $stmt->fetchAll();

// Détail d'une commande si id_commande est passé
$commande_detail = null;
$details_produits = [];
if (isset($_GET['id_commande']) && is_numeric($_GET['id_commande'])) {
    $id_commande = (int)$_GET['id_commande'];
    
    // Récupération de la commande
    $stmt = $db->prepare("
        SELECT c.*, 
               m.pseudo,
               m.nom,
               m.prenom,
               m.email,
               m.adresse,
               m.cp,
               m.ville
        FROM commande c
        JOIN membre m ON c.id_membre = m.id_membre
        WHERE c.id_commande = ?
    ");
    $stmt->execute([$id_commande]);
    $commande_detail = $stmt->fetch();
    
    if ($commande_detail) {
        // Récupération des détails de la commande (produits)
        $stmt = $db->prepare("
            SELECT dc.*,
                   p.date_arrivee,
                   p.date_depart,
                   s.titre as salle_titre,
                   s.ville as salle_ville
            FROM details_commande dc
            JOIN produit p ON dc.id_produit = p.id_produit
            JOIN salle s ON p.id_salle = s.id_salle
            WHERE dc.id_commande = ?
        ");
        $stmt->execute([$id_commande]);
        $details_produits = $stmt->fetchAll();
    }
}

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <h1 class="text-center mb-2">Gestion des commandes</h1>
    
    <!-- Chiffre d'affaires -->
    <div class="card mb-2 ca-box">
        <h2 class="card-title">Chiffre d'affaires</h2>
        <p class="ca-amount">
            <?php echo formatPrice($ca_total); ?>
        </p>
    </div>
    
    <!-- Détail d'une commande -->
    <?php if ($commande_detail): ?>
        <div class="card mb-2">
            <h2 class="card-title">Détail de la commande #<?php echo $commande_detail['id_commande']; ?></h2>
            
            <div class="commande-detail-info">
                <div class="detail-section">
                    <h3>Informations client</h3>
                    <p><strong>Membre :</strong> <?php echo htmlspecialchars($commande_detail['pseudo']); ?> 
                       (<?php echo htmlspecialchars($commande_detail['prenom'] . ' ' . $commande_detail['nom']); ?>)</p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($commande_detail['email']); ?></p>
                    <p><strong>Adresse :</strong> <?php echo htmlspecialchars($commande_detail['adresse']); ?>, 
                       <?php echo htmlspecialchars($commande_detail['cp']); ?> <?php echo htmlspecialchars($commande_detail['ville']); ?></p>
                </div>
                
                <div class="detail-section">
                    <h3>Informations commande</h3>
                    <p><strong>Date :</strong> <?php echo formatDate($commande_detail['date'], 'd/m/Y H:i'); ?></p>
                    <p><strong>Statut :</strong> 
                        <span class="statut-badge statut-<?php echo $commande_detail['statut']; ?>">
                            <?php 
                            $statuts = [
                                'en_attente' => 'En attente',
                                'validee' => 'Validée',
                                'annulee' => 'Annulée'
                            ];
                            echo $statuts[$commande_detail['statut']] ?? $commande_detail['statut'];
                            ?>
                        </span>
                    </p>
                    <p><strong>Montant total :</strong> <?php echo formatPrice($commande_detail['montant']); ?></p>
                </div>
            </div>
            
            <div class="detail-section mt-2">
                <h3>Produits commandés</h3>
                <?php if (empty($details_produits)): ?>
                    <p>Aucun produit dans cette commande.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Salle</th>
                                <th>Ville</th>
                                <th>Date arrivée</th>
                                <th>Date départ</th>
                                <th>Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details_produits as $detail): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detail['salle_titre']); ?></td>
                                    <td><?php echo htmlspecialchars($detail['salle_ville']); ?></td>
                                    <td><?php echo formatDate($detail['date_arrivee'], 'd/m/Y H:i'); ?></td>
                                    <td><?php echo formatDate($detail['date_depart'], 'd/m/Y H:i'); ?></td>
                                    <td><?php echo formatPrice($detail['prix']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="mt-2">
                <a href="gestion_commandes.php" class="btn btn-secondary">Retour à la liste</a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Liste des commandes -->
    <div class="card">
        <h2 class="card-title">Liste des commandes (<?php echo count($commandes); ?>)</h2>
        
        <?php if (empty($commandes)): ?>
            <p>Aucune commande enregistrée.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Commande</th>
                            <th>Date</th>
                            <th>Membre</th>
                            <th>Email</th>
                            <th>Nb Produits</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $commande): ?>
                            <tr>
                                <td>
                                    <a href="?id_commande=<?php echo $commande['id_commande']; ?>" 
                                       class="link-commande">
                                        #<?php echo $commande['id_commande']; ?>
                                    </a>
                                </td>
                                <td><?php echo formatDate($commande['date'], 'd/m/Y H:i'); ?></td>
                                <td><?php echo htmlspecialchars($commande['pseudo']); ?></td>
                                <td><?php echo htmlspecialchars($commande['email']); ?></td>
                                <td><?php echo $commande['nb_produits']; ?></td>
                                <td><?php echo formatPrice($commande['montant']); ?></td>
                                <td>
                                    <span class="statut-badge statut-<?php echo $commande['statut']; ?>">
                                        <?php 
                                        $statuts = [
                                            'en_attente' => 'En attente',
                                            'validee' => 'Validée',
                                            'annulee' => 'Annulée'
                                        ];
                                        echo $statuts[$commande['statut']] ?? $commande['statut'];
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>