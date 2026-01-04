<?php
/**
 * Script de test pour vérifier salles et produits
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Salles et Produits</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Test : Salles et Produits</h1>
    
    <?php
    try {
        $db = getDB();
        
        // Test 1: Compter les salles
        echo "<h2>1. Test des Salles</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM salle");
        $count_salles = $stmt->fetch();
        echo "<div class='info'><strong>Nombre total de salles :</strong> " . $count_salles['nb'] . "</div>";
        
        if ($count_salles['nb'] > 0) {
            // Afficher les 5 premières salles
            $stmt = $db->query("SELECT * FROM salle LIMIT 5");
            $salles = $stmt->fetchAll();
            echo "<table>";
            echo "<thead><tr><th>ID</th><th>Titre</th><th>Ville</th><th>Capacité</th><th>Catégorie</th></tr></thead><tbody>";
            foreach ($salles as $salle) {
                echo "<tr>";
                echo "<td>" . $salle['id_salle'] . "</td>";
                echo "<td>" . htmlspecialchars($salle['titre']) . "</td>";
                echo "<td>" . htmlspecialchars($salle['ville']) . "</td>";
                echo "<td>" . $salle['capacite'] . "</td>";
                echo "<td>" . $salle['categorie'] . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='error'>❌ Aucune salle dans la base de données !</div>";
        }
        
        // Test 2: Compter les produits
        echo "<h2>2. Test des Produits</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM produit");
        $count_produits = $stmt->fetch();
        echo "<div class='info'><strong>Nombre total de produits :</strong> " . $count_produits['nb'] . "</div>";
        
        $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE etat = 0");
        $count_produits_dispo = $stmt->fetch();
        echo "<div class='info'><strong>Produits avec etat = 0 (disponibles) :</strong> " . $count_produits_dispo['nb'] . "</div>";
        
        $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE date_arrivee >= NOW()");
        $count_produits_futurs = $stmt->fetch();
        echo "<div class='info'><strong>Produits avec date_arrivee >= NOW() :</strong> " . $count_produits_futurs['nb'] . "</div>";
        
        // Test 3: La requête actuelle de index.php
        echo "<h2>3. Test de la requête index.php (actuelle)</h2>";
        echo "<div class='info'>Requête : FROM produit JOIN salle WHERE etat = 0 AND date_arrivee >= NOW()</div>";
        
        $stmt = $db->query("
            SELECT p.*, 
                   s.titre as salle_titre,
                   s.ville,
                   s.cp,
                   s.capacite,
                   s.photo
            FROM produit p
            JOIN salle s ON p.id_salle = s.id_salle
            WHERE p.etat = 0 
            AND p.date_arrivee >= NOW()
            ORDER BY p.id_produit DESC
            LIMIT 3
        ");
        $produits_actuels = $stmt->fetchAll();
        
        if (count($produits_actuels) > 0) {
            echo "<div class='success'>✅ La requête actuelle retourne " . count($produits_actuels) . " résultat(s)</div>";
            echo "<table>";
            echo "<thead><tr><th>ID Produit</th><th>Salle</th><th>Ville</th><th>Date Arrivée</th><th>Date Départ</th><th>Prix</th><th>État</th></tr></thead><tbody>";
            foreach ($produits_actuels as $p) {
                echo "<tr>";
                echo "<td>" . $p['id_produit'] . "</td>";
                echo "<td>" . htmlspecialchars($p['salle_titre']) . "</td>";
                echo "<td>" . htmlspecialchars($p['ville']) . "</td>";
                echo "<td>" . $p['date_arrivee'] . "</td>";
                echo "<td>" . $p['date_depart'] . "</td>";
                echo "<td>" . number_format($p['prix'] / 100, 2, ',', ' ') . " €</td>";
                echo "<td>" . ($p['etat'] == 0 ? 'Disponible (0)' : 'Réservé (1)') . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='error'>❌ La requête actuelle ne retourne aucun résultat</div>";
        }
        
        // Test 4: Alternative - Partir des salles qui ont des produits disponibles
        echo "<h2>4. Alternative : Salles qui ont des produits disponibles</h2>";
        echo "<div class='info'>Requête : FROM salle JOIN produit WHERE etat = 0 AND date_arrivee >= NOW()</div>";
        
        $stmt = $db->query("
            SELECT s.*, 
                   p.id_produit,
                   p.date_arrivee,
                   p.date_depart,
                   p.prix,
                   p.etat
            FROM salle s
            JOIN produit p ON s.id_salle = p.id_salle
            WHERE p.etat = 0 
            AND p.date_arrivee >= NOW()
            ORDER BY p.id_produit DESC
            LIMIT 3
        ");
        $salles_avec_produits = $stmt->fetchAll();
        
        if (count($salles_avec_produits) > 0) {
            echo "<div class='success'>✅ Cette alternative retourne " . count($salles_avec_produits) . " résultat(s)</div>";
            echo "<table>";
            echo "<thead><tr><th>ID Salle</th><th>Titre Salle</th><th>Ville</th><th>ID Produit</th><th>Date Arrivée</th><th>Prix</th></tr></thead><tbody>";
            foreach ($salles_avec_produits as $sp) {
                echo "<tr>";
                echo "<td>" . $sp['id_salle'] . "</td>";
                echo "<td>" . htmlspecialchars($sp['titre']) . "</td>";
                echo "<td>" . htmlspecialchars($sp['ville']) . "</td>";
                echo "<td>" . $sp['id_produit'] . "</td>";
                echo "<td>" . $sp['date_arrivee'] . "</td>";
                echo "<td>" . number_format($sp['prix'] / 100, 2, ',', ' ') . " €</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='error'>❌ Cette alternative ne retourne aucun résultat</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
    
    <hr>
    <p><a href="index.php">← Retour à l'accueil</a> | <a href="test-index-query.php">Test Requête Index</a></p>
</body>
</html>