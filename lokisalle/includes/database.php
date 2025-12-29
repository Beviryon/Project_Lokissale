<?php

require_once dirname(__FILE__) . '/config.php';

static $pdo = null;
/**
 * Fonction pour obtenir la connexion à la base de données 
 * Utilise une variable statique pour réutiliser la même connexion 
 * @return PDO l'objet pdo pour exécuter des requêtes sql
 */
function getDB() {
    global $pdo;

    if ($pdo === null ) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Génère des exceptions en cas d'erreur
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retourne un tableau associatif par défaut
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Utilise les vraies requêtes préparées
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }

    }

    return $pdo;
}

?>