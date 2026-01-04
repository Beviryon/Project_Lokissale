<?php
/**
 * Page profil utilisateur (profil.php)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérification de l'authentification (selon cahier des charges)
requireLogin();

$db = getDB();
$id_membre = $_SESSION['membre']['id_membre'];

// Récupération des informations du membre depuis la session
$membre = $_SESSION['membre'];

// Récupération complète des informations depuis la base
$stmt = $db->prepare("SELECT * FROM membre WHERE id_membre = ?");
$stmt->execute([$id_membre]);
$membre_complet = $stmt->fetch();

// Récupération des commandes
$stmt = $db->prepare("
    SELECT c.*
    FROM commande c
    WHERE c.id_membre = ?
    ORDER BY c.date DESC
    LIMIT 10
");
$stmt->execute([$id_membre]);
$commandes = $stmt->fetchAll();

$pageTitle = 'Mon profil - LOKISALLE';
$pageCSS = 'profil.css';

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Profil</p>
    </div>
    
    <!-- Zone 3 et Zone 4 : Deux colonnes -->
    <div class="profil-layout">
        <!-- Zone 3 : Informations du membre (colonne gauche) -->
        <div class="zone-3-informations">
            <h2>voici vos informations</h2>
            
            <div class="informations-list">
                <p><strong>Votre pseudo:</strong> <?php echo htmlspecialchars($membre_complet['pseudo']); ?></p>
                <p><strong>votre email est:</strong> <?php echo htmlspecialchars($membre_complet['email']); ?></p>
                <p><strong>votre ville est:</strong> <?php echo htmlspecialchars($membre_complet['ville']); ?></p>
                <p><strong>votre cp est:</strong> <?php echo htmlspecialchars($membre_complet['cp']); ?></p>
                <p><strong>votre adresse est:</strong> <?php echo htmlspecialchars($membre_complet['adresse']); ?></p>
            </div>
            
            <div class="mt-2">
                <a href="<?php echo SITE_URL; ?>/pages/modifier-profil.php" class="btn-mettre-a-jour">Mettre à jour mes informations</a>
            </div>
        </div>
        
        <!-- Zone 4 : Dernières commandes (colonne droite) -->
        <div class="zone-4-commandes">
            <h2>vos dernières commandes</h2>
            
            <?php if (empty($commandes)): ?>
                <p>Aucune commande pour le moment.</p>
            <?php else: ?>
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th>Numero de suivi</th>
                            <th>date</th>
                            <th>Facture</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $commande): ?>
                            <tr>
                                <td><?php echo $commande['id_commande']; ?></td>
                                <td><?php echo formatDate($commande['date'], 'd/m/Y'); ?></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/pages/facture.php?id_commande=<?php echo $commande['id_commande']; ?>" class="link-voir">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>