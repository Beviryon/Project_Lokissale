<?php
/**
 * Page de déconnexion
 * Déconnecte l'utilisateur et le redirige
 */

header('Content-Type: text/html; charset=UTF-8').

require_once '../includes/config.php';
require_once '../includes/functions.php';

// S'assurer que la session est démarrée (config.php le fait déjà, mais on s'assure)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vider toutes les variables de session
$_SESSION = [];

// Supprimer le cookie "Se souvenir de moi" s'il existe
if (isset($_COOKIE['lokisalle_pseudo'])) {
    setcookie('lokisalle_pseudo', '', time() - 3600, '/');
    unset($_COOKIE['lokisalle_pseudo']);
}

// Détruire le cookie de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Détruire la session
session_destroy();

// Redémarrer une nouvelle session pour le message flash
session_start();
$_SESSION['message'] = 'Vous avez été déconnecté avec succès.';

// Redirection vers l'accueil
redirect('pages/index.php');