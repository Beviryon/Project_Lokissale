<?php
/**
 * Page de déconnexion
 * Déconnecte l'utilisateur et le redirige
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = 'Vous avez été déconnecté avec succès.';

if (isset($_COOKIE['lokisalle_pseudo'])) {
    setcookie('lokisalle_pseudo', '', time() - 3600, '/', '', false, true);
    unset($_COOKIE['lokisalle_pseudo']);
}

$_SESSION = [];

$session_name = session_name();
$session_params = session_get_cookie_params();
if (isset($_COOKIE[$session_name])) {
    setcookie(
        $session_name, 
        '', 
        time() - 3600, 
        $session_params['path'], 
        $session_params['domain'], 
        $session_params['secure'], 
        $session_params['httponly']
    );
}
session_regenerate_id(true);

$_SESSION['message'] = $message;

redirect('index.php');

