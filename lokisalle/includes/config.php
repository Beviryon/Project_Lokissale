<?php



//Configuration de la base de données 
define('DB_HOST', 'localhost');
define('DB_NAME', 'lokisalle');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');


define('SITE_NAME', 'lokisalle ');
define('SITE_URL', 'http://localhost/lokisalle');

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
