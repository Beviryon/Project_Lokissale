<?php
/**
 * Fichier de test de la connexion à la base de données
 * Affiche les informations de connexion ET le contenu des tables
 * À supprimer après les tests (sécurité)
 */

// Inclusion des fichiers nécessaires
require_once 'includes/config.php';
require_once 'includes/database.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de connexion - LOKISALLE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #2c3e50;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin: 10px 0;
        }
        .count {
            font-weight: bold;
            color: #2196F3;
        }
    </style>
</head>
<body>
    <h1> Test de connexion à la base de données</h1>

<?php
try {
    // Test 1 : Connexion
    echo "<div class='container'>";
    echo "<h2>Test 1 : Connexion à la base de données</h2>";
    $db = getDB();
    
    if ($db) {
        echo "<p class='success'> Connexion réussie !</p>";
        echo "<p>Type de connexion : <strong>" . get_class($db) . "</strong></p>";
    } else {
        echo "<p class='error'> Échec de la connexion</p>";
    }
    echo "</div>";
    
    // Test 2 : Informations de la base
    echo "<div class='container'>";
    echo "<h2>Test 2 : Informations de la base de données</h2>";
    $stmt = $db->query("SELECT DATABASE() as db_name, VERSION() as db_version");
    $result = $stmt->fetch();
    
    echo "<div class='info-box'>";
    echo "<p><strong>Base de données connectée :</strong> " . htmlspecialchars($result['db_name']) . "</p>";
    echo "<p><strong>Version MySQL :</strong> " . htmlspecialchars($result['db_version']) . "</p>";
    echo "</div>";
    echo "</div>";
    
    // Test 3 : Liste des tables
    echo "<div class='container'>";
    echo "<h2>Test 3 : Tables de la base de données</h2>";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p class='success'> <span class='count'>" . count($tables) . "</span> table(s) trouvée(s) :</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li><strong>" . htmlspecialchars($table) . "</strong></li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'> Aucune table trouvée. Pensez à exécuter le fichier database/schema.sql</p>";
    }
    echo "</div>";
    
    // Test 4 : Contenu de la table MEMBRE
    if (in_array('membre', $tables)) {
        echo "<div class='container'>";
        echo "<h2> Table : MEMBRE</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM membre");
        $count = $stmt->fetch();
        echo "<p class='count'>Nombre total : " . $count['nb'] . " membre(s)</p>";
        
        $stmt = $db->query("SELECT id_membre, pseudo, nom, prenom, email, ville, statut FROM membre ORDER BY id_membre LIMIT 20");
        $membres = $stmt->fetchAll();
        
        if (count($membres) > 0) {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>ID</th><th>Pseudo</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Ville</th><th>Statut</th>";
            echo "</tr></thead><tbody>";
            foreach ($membres as $membre) {
                $statut = $membre['statut'] == 1 ? 'Admin' : 'Membre';
                echo "<tr>";
                echo "<td>" . $membre['id_membre'] . "</td>";
                echo "<td>" . htmlspecialchars($membre['pseudo']) . "</td>";
                echo "<td>" . htmlspecialchars($membre['nom']) . "</td>";
                echo "<td>" . htmlspecialchars($membre['prenom']) . "</td>";
                echo "<td>" . htmlspecialchars($membre['email']) . "</td>";
                echo "<td>" . htmlspecialchars($membre['ville']) . "</td>";
                echo "<td>" . $statut . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            if ($count['nb'] > 20) {
                echo "<p><em>(Affichage des 20 premiers résultats sur " . $count['nb'] . ")</em></p>";
            }
        } else {
            echo "<p class='warning'> Aucun membre dans la table</p>";
        }
        echo "</div>";
    }
    
    // Test 5 : Contenu de la table SALLE
    if (in_array('salle', $tables)) {
        echo "<div class='container'>";
        echo "<h2> Table : SALLE</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM salle");
        $count = $stmt->fetch();
        echo "<p class='count'>Nombre total : " . $count['nb'] . " salle(s)</p>";
        
        $stmt = $db->query("SELECT id_salle, titre, ville, capacite, categorie FROM salle ORDER BY id_salle LIMIT 20");
        $salles = $stmt->fetchAll();
        
        if (count($salles) > 0) {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>ID</th><th>Titre</th><th>Ville</th><th>Capacité</th><th>Catégorie</th>";
            echo "</tr></thead><tbody>";
            foreach ($salles as $salle) {
                echo "<tr>";
                echo "<td>" . $salle['id_salle'] . "</td>";
                echo "<td>" . htmlspecialchars($salle['titre']) . "</td>";
                echo "<td>" . htmlspecialchars($salle['ville']) . "</td>";
                echo "<td>" . $salle['capacite'] . " personnes</td>";
                echo "<td>" . htmlspecialchars($salle['categorie']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            if ($count['nb'] > 20) {
                echo "<p><em>(Affichage des 20 premiers résultats sur " . $count['nb'] . ")</em></p>";
            }
        } else {
            echo "<p class='warning'> Aucune salle dans la table</p>";
        }
        echo "</div>";
    }
    
    // Test 6 : Contenu de la table PRODUIT
    if (in_array('produit', $tables)) {
        echo "<div class='container'>";
        echo "<h2> Table : PRODUIT</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM produit");
        $count = $stmt->fetch();
        echo "<p class='count'>Nombre total : " . $count['nb'] . " produit(s)</p>";
        
        $stmt = $db->query("
            SELECT p.id_produit, p.date_arrivee, p.date_depart, p.prix, p.etat, 
                   s.titre as salle_titre, pr.code_promo
            FROM produit p
            LEFT JOIN salle s ON p.id_salle = s.id_salle
            LEFT JOIN promotion pr ON p.id_promo = pr.id_promo
            ORDER BY p.id_produit DESC
            LIMIT 20
        ");
        $produits = $stmt->fetchAll();
        
        if (count($produits) > 0) {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>ID</th><th>Salle</th><th>Date Arrivée</th><th>Date Départ</th><th>Prix (centimes)</th><th>État</th><th>Code Promo</th>";
            echo "</tr></thead><tbody>";
            foreach ($produits as $produit) {
                $etat = $produit['etat'] == 0 ? 'Disponible' : 'Réservé';
                $date_arr = new DateTime($produit['date_arrivee']);
                $date_dep = new DateTime($produit['date_depart']);
                echo "<tr>";
                echo "<td>" . $produit['id_produit'] . "</td>";
                echo "<td>" . htmlspecialchars($produit['salle_titre'] ?? 'N/A') . "</td>";
                echo "<td>" . $date_arr->format('d/m/Y H:i') . "</td>";
                echo "<td>" . $date_dep->format('d/m/Y H:i') . "</td>";
                echo "<td>" . number_format($produit['prix'] / 100, 2, ',', ' ') . " €</td>";
                echo "<td>" . $etat . "</td>";
                echo "<td>" . htmlspecialchars($produit['code_promo'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            if ($count['nb'] > 20) {
                echo "<p><em>(Affichage des 20 premiers résultats sur " . $count['nb'] . ")</em></p>";
            }
        } else {
            echo "<p class='warning'> Aucun produit dans la table</p>";
        }
        echo "</div>";
    }
    
    // Test 7 : Contenu de la table COMMANDE
    if (in_array('commande', $tables)) {
        echo "<div class='container'>";
        echo "<h2> Table : COMMANDE</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM commande");
        $count = $stmt->fetch();
        echo "<p class='count'>Nombre total : " . $count['nb'] . " commande(s)</p>";
        
        $stmt = $db->query("
            SELECT c.id_commande, c.montant, c.date, 
                   m.pseudo, m.email
            FROM commande c
            LEFT JOIN membre m ON c.id_membre = m.id_membre
            ORDER BY c.id_commande DESC
            LIMIT 20
        ");
        $commandes = $stmt->fetchAll();
        
        if (count($commandes) > 0) {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>ID Commande</th><th>Membre</th><th>Email</th><th>Montant</th><th>Date</th>";
            echo "</tr></thead><tbody>";
            foreach ($commandes as $commande) {
                $date = new DateTime($commande['date']);
                echo "<tr>";
                echo "<td>" . $commande['id_commande'] . "</td>";
                echo "<td>" . htmlspecialchars($commande['pseudo'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($commande['email'] ?? 'N/A') . "</td>";
                echo "<td>" . number_format($commande['montant'] / 100, 2, ',', ' ') . " €</td>";
                echo "<td>" . $date->format('d/m/Y H:i') . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            if ($count['nb'] > 20) {
                echo "<p><em>(Affichage des 20 premiers résultats sur " . $count['nb'] . ")</em></p>";
            }
        } else {
            echo "<p class='warning'> Aucune commande dans la table</p>";
        }
        echo "</div>";
    }
    
    // Test 8 : Contenu de la table PROMOTION
    if (in_array('promotion', $tables)) {
        echo "<div class='container'>";
        echo "<h2> Table : PROMOTION</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM promotion");
        $count = $stmt->fetch();
        echo "<p class='count'>Nombre total : " . $count['nb'] . " promotion(s)</p>";
        
        $stmt = $db->query("SELECT id_promo, code_promo, reduction FROM promotion ORDER BY id_promo LIMIT 20");
        $promos = $stmt->fetchAll();
        
        if (count($promos) > 0) {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>ID</th><th>Code Promo</th><th>Réduction</th>";
            echo "</tr></thead><tbody>";
            foreach ($promos as $promo) {
                echo "<tr>";
                echo "<td>" . $promo['id_promo'] . "</td>";
                echo "<td><strong>" . htmlspecialchars($promo['code_promo']) . "</strong></td>";
                echo "<td>" . $promo['reduction'] . "%</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            if ($count['nb'] > 20) {
                echo "<p><em>(Affichage des 20 premiers résultats sur " . $count['nb'] . ")</em></p>";
            }
        } else {
            echo "<p class='warning'> Aucune promotion dans la table</p>";
        }
        echo "</div>";
    }
    
    // Test 9 : Contenu de la table AVIS
    if (in_array('avis', $tables)) {
        echo "<div class='container'>";
        echo "<h2> Table : AVIS</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM avis");
        $count = $stmt->fetch();
        echo "<p class='count'>Nombre total : " . $count['nb'] . " avis</p>";
        
        $stmt = $db->query("
            SELECT a.id_avis, a.note, a.date, a.commentaire,
                   m.pseudo, s.titre as salle_titre
            FROM avis a
            LEFT JOIN membre m ON a.id_membre = m.id_membre
            LEFT JOIN salle s ON a.id_salle = s.id_salle
            ORDER BY a.id_avis DESC
            LIMIT 20
        ");
        $avis = $stmt->fetchAll();
        
        if (count($avis) > 0) {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>ID</th><th>Membre</th><th>Salle</th><th>Note</th><th>Date</th><th>Commentaire</th>";
            echo "</tr></thead><tbody>";
            foreach ($avis as $un_avis) {
                $date = new DateTime($un_avis['date']);
                $commentaire = mb_substr($un_avis['commentaire'], 0, 50) . (strlen($un_avis['commentaire']) > 50 ? '...' : '');
                echo "<tr>";
                echo "<td>" . $un_avis['id_avis'] . "</td>";
                echo "<td>" . htmlspecialchars($un_avis['pseudo'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($un_avis['salle_titre'] ?? 'N/A') . "</td>";
                echo "<td>" . str_repeat('⭐', $un_avis['note']) . " (" . $un_avis['note'] . "/5)</td>";
                echo "<td>" . $date->format('d/m/Y H:i') . "</td>";
                echo "<td>" . htmlspecialchars($commentaire) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            if ($count['nb'] > 20) {
                echo "<p><em>(Affichage des 20 premiers résultats sur " . $count['nb'] . ")</em></p>";
            }
        } else {
            echo "<p class='warning'> Aucun avis dans la table</p>";
        }
        echo "</div>";
    }
    
    // Test 10 : Contenu de la table NEWSLETTER
    if (in_array('newsletter', $tables)) {
        echo "<div class='container'>";
        echo "<h2> Table : NEWSLETTER</h2>";
        $stmt = $db->query("SELECT COUNT(*) as nb FROM newsletter");
        $count = $stmt->fetch();
        echo "<p class='count'>Nombre total : " . $count['nb'] . " abonné(s)</p>";
        
        $stmt = $db->query("
            SELECT n.id_newsletter, m.pseudo, m.email
            FROM newsletter n
            LEFT JOIN membre m ON n.id_membre = m.id_membre
            ORDER BY n.id_newsletter
            LIMIT 20
        ");
        $newsletters = $stmt->fetchAll();
        
        if (count($newsletters) > 0) {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>ID</th><th>Pseudo</th><th>Email</th>";
            echo "</tr></thead><tbody>";
            foreach ($newsletters as $newsletter) {
                echo "<tr>";
                echo "<td>" . $newsletter['id_newsletter'] . "</td>";
                echo "<td>" . htmlspecialchars($newsletter['pseudo'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($newsletter['email'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            if ($count['nb'] > 20) {
                echo "<p><em>(Affichage des 20 premiers résultats sur " . $count['nb'] . ")</em></p>";
            }
        } else {
            echo "<p class='warning'> Aucun abonné à la newsletter</p>";
        }
        echo "</div>";
    }
    
    // Test 11 : Statistiques globales
    echo "<div class='container'>";
    echo "<h2> Statistiques globales</h2>";
    echo "<div class='info-box'>";
    
    if (in_array('membre', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as nb FROM membre");
        $nb_membres = $stmt->fetch()['nb'];
        $stmt = $db->query("SELECT COUNT(*) as nb FROM membre WHERE statut = 1");
        $nb_admins = $stmt->fetch()['nb'];
        echo "<p><strong>Membres :</strong> " . $nb_membres . " (dont " . $nb_admins . " administrateur(s))</p>";
    }
    
    if (in_array('salle', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as nb FROM salle");
        $nb_salles = $stmt->fetch()['nb'];
        echo "<p><strong>Salles :</strong> " . $nb_salles . "</p>";
    }
    
    if (in_array('produit', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE etat = 0");
        $nb_dispo = $stmt->fetch()['nb'];
        $stmt = $db->query("SELECT COUNT(*) as nb FROM produit WHERE etat = 1");
        $nb_reserve = $stmt->fetch()['nb'];
        echo "<p><strong>Produits :</strong> " . ($nb_dispo + $nb_reserve) . " (dont " . $nb_dispo . " disponible(s) et " . $nb_reserve . " réservé(s))</p>";
    }
    
    if (in_array('commande', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) as nb, SUM(montant) as total FROM commande");
        $stats = $stmt->fetch();
        echo "<p><strong>Commandes :</strong> " . $stats['nb'] . "</p>";
        if ($stats['total']) {
            echo "<p><strong>Chiffre d'affaires total :</strong> " . number_format($stats['total'] / 100, 2, ',', ' ') . " €</p>";
        }
    }
    
    echo "</div>";
    echo "</div>";
    
    // Résumé final
    echo "<div class='container'>";
    echo "<hr>";
    echo "<h2 class='success'> Tous les tests sont passés avec succès !</h2>";
    echo "<p><strong>La connexion à la base de données fonctionne correctement.</strong></p>";
    echo "<p class='warning'><em> N'oubliez pas de supprimer ce fichier après les tests pour des raisons de sécurité.</em></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='container'>";
    echo "<h2 class='error'> Erreur de connexion</h2>";
    echo "<p class='error'><strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Vérifications à faire :</h3>";
    echo "<ul>";
    echo "<li>Vérifiez que MySQL/MariaDB est démarré dans XAMPP</li>";
    echo "<li>Vérifiez les paramètres dans <code>includes/config.php</code> :";
    echo "<ul>";
    echo "<li>DB_HOST = " . DB_HOST . "</li>";
    echo "<li>DB_NAME = " . DB_NAME . "</li>";
    echo "<li>DB_USER = " . DB_USER . "</li>";
    echo "<li>DB_PASS = " . (empty(DB_PASS) ? '(vide)' : '*') . "</li>";
    echo "</ul></li>";
    echo "<li>Vérifiez que la base de données '" . DB_NAME . "' existe</li>";
    echo "<li>Si la base n'existe pas, exécutez le fichier <code>database/schema.sql</code> dans phpMyAdmin</li>";
    echo "</ul>";
    echo "</div>";
}
?>

</body>
</html>