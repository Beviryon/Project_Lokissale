<?php
/**
 * Page d'accueil de l'administration
 * Tableau de bord avec statistiques générales
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérification des droits admin
requireAdmin();

$pageTitle = 'Administration - Tableau de bord';
$pageCSS = 'admin.css';
$db = getDB();

// Statistiques générales
$stats = [];

// Nombre de salles
$stmt = $db->query("SELECT COUNT(*) FROM salle");
$stats['nb_salles'] = $stmt->fetchColumn();

// Nombre de produits disponibles
$stmt = $db->query("SELECT COUNT(*) FROM produit WHERE etat = 1");
$stats['nb_produits'] = $stmt->fetchColumn();

// Nombre de membres
$stmt = $db->query("SELECT COUNT(*) FROM membre WHERE statut = 0");
$stats['nb_membres'] = $stmt->fetchColumn();

// Nombre de commandes
$stmt = $db->query("SELECT COUNT(*) FROM commande");
$stats['nb_commandes'] = $stmt->fetchColumn();

// Chiffre d'affaires total
$stmt = $db->query("SELECT SUM(montant) FROM commande WHERE statut = 'validee'");
$stats['ca_total'] = $stmt->fetchColumn() ?? 0;

// Nombre d'avis
$stmt = $db->query("SELECT COUNT(*) FROM avis");
$stats['nb_avis'] = $stmt->fetchColumn();

// Abonnés newsletter
$stmt = $db->query("SELECT COUNT(*) FROM newsletter");
$stats['nb_newsletter'] = $stmt->fetchColumn();

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <h1 class="text-center mb-2">Tableau de bord - Administration</h1>
    
    <!-- Statistiques générales -->
    <div class="stats-grid mb-2">
        <div class="stat-card">
            <h3>Salles</h3>
            <p class="stat-number"><?php echo $stats['nb_salles']; ?></p>
            <a href="gestion-salles.php" class="btn btn-primary">Gérer</a>
        </div>
        
        <div class="stat-card">
            <h3>Produits</h3>
            <p class="stat-number"><?php echo $stats['nb_produits']; ?></p>
            <a href="gestion_produits.php" class="btn btn-primary">Gérer</a>
        </div>
        
        <div class="stat-card">
            <h3>Membres</h3>
            <p class="stat-number"><?php echo $stats['nb_membres']; ?></p>
            <a href="gestion_membres.php" class="btn btn-primary">Gérer</a>
        </div>
        
        <div class="stat-card">
            <h3>Commandes</h3>
            <p class="stat-number"><?php echo $stats['nb_commandes']; ?></p>
            <a href="gestion_commandes.php" class="btn btn-primary">Gérer</a>
        </div>
        
        <div class="stat-card">
            <h3>Chiffre d'affaires</h3>
            <p class="stat-number"><?php echo formatPrice($stats['ca_total']); ?></p>
        </div>
        
        <div class="stat-card"></div>
            <h3>Avis</h3>
            <p class="stat-number"><?php echo $stats['nb_avis']; ?></p>
            <a href="gestion-avis.php" class="btn btn-primary">Gérer</a>
        </div>
        
        <div class="stat-card">
            <h3>Newsletter</h3>
            <p class="stat-number"><?php echo $stats['nb_newsletter']; ?></p>
            <a href="envoi-newsletter.php" class="btn btn-primary">Gérer</a>
        </div>
    </div>
    
    <!-- Menu administration -->
    <div class="card">
        <h2 class="card-title">Menu administration</h2>
        <div class="admin-menu">
            <a href="gestion-salles.php" class="admin-menu-item">
                <h3> Gestion des salles</h3>
                <p>Ajouter, modifier, supprimer des salles</p>
            </a>
            
            <a href="gestion_produits.php" class="admin-menu-item">
                <h3> Gestion des produits</h3>
                <p>Gérer les disponibilités et tarifs</p>
            </a>
            
            <a href="gestion_membres.php" class="admin-menu-item">
                <h3> Gestion des membres</h3>
                <p>Voir et gérer les membres</p>
            </a>
            
            <a href="gestion_commandes.php" class="admin-menu-item">
                <h3> Gestion des commandes</h3>
                <p>Consulter et gérer les commandes</p>
            </a>
            
            <a href="gestion-avis.php" class="admin-menu-item">
                <h3> Gestion des avis</h3>
                <p>Modérer les avis clients</p>
            </a>
            
            <a href="gestion-promos.php" class="admin-menu-item">
                <h3> Gestion des codes promo</h3>
                <p>Créer et gérer les promotions</p>
            </a>
            
            <a href="statistiques.php" class="admin-menu-item">
                <h3> Statistiques</h3>
                <p>Voir les statistiques détaillées</p>
            </a>
            
            <a href="envoi-newsletter.php" class="admin-menu-item">
                <h3> Envoyer la newsletter</h3>
                <p>Envoyer des emails aux abonnés</p>
            </a>
        </div>
    </div>
</div>

<?php include '../includes/bas.inc.php'; ?>