<?php
/**
 * Fichier contenant les fonctions utilitaires réutilisables
 */

require_once dirname(__FILE__) . '/database.php';

/**
 * Vérifie si un utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['membre']) && !empty($_SESSION['membre']);
}

/**
 * Vérifie si l'utilisateur connecté est administrateur
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['membre']['statut']) && $_SESSION['membre']['statut'] == 1;
}

/**
 * Redirige vers une page avec un message
 */
function redirect($url, $message = '') {
    // Sauvegarder le message dans la session si fourni
    if (!empty($message)) {
        $_SESSION['message'] = $message;
    }
    
    // Si l'URL ne commence pas par http, ajouter SITE_URL
    if (strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        $url = SITE_URL . '/' . ltrim($url, '/');
    } elseif (strpos($url, '/') === 0) {
        // Si l'URL commence par /, ajouter SITE_URL
        $url = SITE_URL . $url;
    }
    
    // Redirection HTTP (302 Found - redirection temporaire)
    header("Location: " . $url, true, 302);
    exit();
}

/**
 * Affiche et supprime les messages flash
 */
function getFlashMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return '';
}

/**
 * Nettoie les données d'entrée pour éviter les injections XSS
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Valide une adresse email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Formate un prix en euros
 */
function formatPrice($price) {
    return number_format($price / 100, 2, ',', ' ') . ' €';
}

/**
 * Formate une date au format français
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Vérifie si une date est valide
 */
function isValidDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Vérifie si deux dates se chevauchent
 */
function datesOverlap($start1, $end1, $start2, $end2) {
    return ($start1 < $end2 && $end1 > $start2);
}

/**
 * Génère un code promo unique
 */
function generatePromoCode($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Vérifie si un fichier uploadé est une image valide
 */
function isValidImage($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($file['type'], $allowed) && $file['size'] <= 5000000; // 5MB max
}

/**
 * Upload un fichier image
 */
function uploadImage($file, $destination) {
    if (!isValidImage($file)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $path = UPLOAD_PATH . $destination . '/' . $filename;
    
    // Créer le dossier s'il n'existe pas
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $filename;
    }
    
    return false;
}

/**
 * Supprime un fichier
 */
function deleteFile($filename, $folder = '') {
    $path = UPLOAD_PATH . $folder . '/' . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Calcule le prix avec réduction
 */
function calculatePriceWithDiscount($price, $discount) {
    return $price - ($price * $discount / 100);
}

/**
 * Vérifie l'accès admin et redirige si nécessaire
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php', 'Accès refusé. Vous devez être administrateur.');
    }
}

/**
 * Vérifie l'authentification et redirige si nécessaire
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/connexion.php', 'Vous devez être connecté pour accéder à cette page.');
    }
}
?>