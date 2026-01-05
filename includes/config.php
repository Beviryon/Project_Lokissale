<?php



//Configuration de la base de données 
define('DB_HOST', 'localhost');
define('DB_NAME', 'lokisalle');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');


define('SITE_NAME', 'lokisalle ');
// define('SITE_URL', 'http://localhost/Project_Lokissale');

if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Méthode 1 : Utiliser SCRIPT_NAME si disponible (contexte web)
    if (isset($_SERVER['SCRIPT_NAME']) && !empty($_SERVER['SCRIPT_NAME'])) {
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Si on est dans un sous-dossier, remonter d'un niveau
        if (preg_match('#/(auth|admin|pages|actions|includes)(/.*)?$#', $script_dir)) {
            $script_dir = dirname($script_dir);
        }
        
        $base_path = rtrim($script_dir, '/');
    } 
    // Méthode 2 : Utiliser REQUEST_URI si disponible
    elseif (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
        $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $script_dir = dirname($request_uri);
        
        if (preg_match('#/(auth|admin|pages|actions|includes)(/.*)?$#', $script_dir)) {
            $script_dir = dirname($script_dir);
        }
        
        $base_path = rtrim($script_dir, '/');
    }
    // Méthode 3 : Fallback avec DOCUMENT_ROOT
    else {
        $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
        $config_dir = str_replace('\\', '/', _DIR_);
        
        if (!empty($doc_root) && strpos($config_dir, $doc_root) === 0) {
            $relative_path = substr($config_dir, strlen($doc_root));
            if (strpos($relative_path, '/includes') !== false) {
                $relative_path = dirname($relative_path);
            }
            $base_path = rtrim($relative_path, '/');
        } else {
            // Dernier recours : valeur par défaut (à modifier si nécessaire)
            $base_path = ''; // Projet à la racine de htdocs
        }
    }
    
    // Nettoyer le chemin
    if (empty($base_path) || $base_path === '/') {
        $base_path = '';
    }
    
    define('SITE_URL', $protocol . '://' . $host . $base_path);
}

define('ROOT_PATH', dirname(__FILE__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');


ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie.secure', 0);


if (session_status() === PHP_SESSION_NONE) {
session_start();
}


error_reporting(E_ALL);
ini_set('display_errors', 1);


?>
