<?php
/**
 * Script pour mettre à jour les dates des produits pour qu'elles soient dans le futur
 * Ce script ajoute 1 an à toutes les dates des produits pour les rendre disponibles
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Correction des dates produits</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .error { color: red; background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336; }
        .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-left: 4px solid #ff9800; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #45a049; }
        .danger { background: #f44336; }
        .danger:hover { background: #da190b; }
    </style>
</head>
<body>
    <h1>🔧 Correction des dates des produits</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_dates'])) {
        try {
            $db = getDB();
            
            // Vérifier la date actuelle
            $stmt = $db->query("SELECT NOW() as now");
            $now = $stmt->fetch()['now'];
            echo "<div class='info'><strong>Date/heure actuelle MySQL :</strong> " . $now . "</div>";
            
            // Compter les produits avant
            $stmt = $db->query("SELECT COUNT(*) as nb FROM produit");
            $count_before = $stmt->fetch()['nb'];
            
            // Mettre à jour les dates : ajouter 2 ans pour être sûr
            // On met la date d'arrivée à aujourd'hui + 30 jours minimum
            $stmt = $db->prepare("
                UPDATE produit 
                SET date_arrivee = DATE_ADD(NOW(), INTERVAL 30 DAY),
                    date_depart = DATE_ADD(DATE_ADD(NOW(), INTERVAL 30 DAY), INTERVAL 8 HOUR)
                WHERE date_arrivee < NOW()
            ");
            $stmt->execute();
            $updated = $stmt->rowCount();
            
            echo "<div class='success'>✅ Mise à jour effectuée !</div>";
            echo "<div class='info'><strong>Produits modifiés :</strong> " . $updated . " sur " . $count_before . "</div>";
            
            // Vérifier après
            $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE date_arrivee >= NOW()");
            $count_after = $stmt->fetch()['nb'];
            echo "<div class='info'><strong>Produits avec date_arrivee >= NOW() maintenant :</strong> " . $count_after . "</div>";
            
            if ($count_after > 0) {
                echo "<div class='success'>✅ Excellent ! Les produits sont maintenant disponibles.</div>";
                echo "<p><a href='index.php'><button>Voir la page d'accueil</button></a></p>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        try {
            $db = getDB();
            
            // Vérifier l'état actuel
            $stmt = $db->query("SELECT NOW() as now");
            $now = $stmt->fetch()['now'];
            echo "<div class='info'><strong>Date/heure actuelle MySQL :</strong> " . $now . "</div>";
            
            $stmt = $db->query("SELECT COUNT(*) as nb FROM produit");
            $count_all = $stmt->fetch()['nb'];
            
            $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE date_arrivee < NOW()");
            $count_past = $stmt->fetch()['nb'];
            
            $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE date_arrivee >= NOW()");
            $count_future = $stmt->fetch()['nb'];
            
            $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE etat = 0");
            $count_etat0 = $stmt->fetch()['nb'];
            
            echo "<div class='info'>";
            echo "<strong>État actuel :</strong><br>";
            echo "- Total produits : " . $count_all . "<br>";
            echo "- Produits avec dates passées : " . $count_past . "<br>";
            echo "- Produits avec dates futures : " . $count_future . "<br>";
            echo "- Produits avec etat = 0 (disponibles) : " . $count_etat0 . "<br>";
            echo "</div>";
            
            if ($count_past > 0) {
                echo "<div class='warning'>⚠ Il y a " . $count_past . " produit(s) avec des dates dans le passé.</div>";
                echo "<p>Ce script va mettre à jour les dates pour qu'elles soient dans le futur (aujourd'hui + 30 jours).</p>";
                echo "<form method='POST'>";
                echo "<button type='submit' name='fix_dates'>Corriger les dates</button>";
                echo "</form>";
            } else {
                echo "<div class='success'>✅ Tous les produits ont des dates futures.</div>";
            }
            
            // Afficher quelques exemples
            $stmt = $db->query("
                SELECT id_produit, date_arrivee, date_depart, etat 
                FROM produit 
                ORDER BY date_arrivee ASC 
                LIMIT 5
            ");
            $exemples = $stmt->fetchAll();
            if (count($exemples) > 0) {
                echo "<div class='info'>";
                echo "<strong>Exemples de produits :</strong><br>";
                foreach ($exemples as $p) {
                    $status = $p['date_arrivee'] < $now ? '❌ Passé' : '✅ Futur';
                    echo "- Produit #" . $p['id_produit'] . " : " . $p['date_arrivee'] . " (" . $status . "), etat = " . $p['etat'] . "<br>";
                }
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    ?>
    
    <hr>
    <p><a href="index.php">← Retour à l'accueil</a> | <a href="test-salles-produits.php">Test Salles/Produits</a></p>
</body>
</html>