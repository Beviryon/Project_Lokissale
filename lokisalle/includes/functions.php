<?php

require_once __DIR__ . '/database.php';

/**
  * Vérifie si un utilisateur est connecté 
  */

function isloggedIn() {
   return isset($_SESSION['membre']['statut']) && $_SESSION['membre']['statut'] == 1;
}

function isAdmin() {
   return isloggedIn() && isset($_SESSION['membre']['statut']) && $_SESSION['membre']['statut'] == 1;
}

function redirect($url, $message = '') {
   if (!empty($message)) {
      $_SESSION['message'] = $message;
   }
   //si l'url ne commence pas par http, ajoute SITE_URL
   if (strpos($url, 'http') !== 0) {
      $url = SITE_URL . '/' . ltrim($url, '/');
   }
   header("Location: $url");
   exit();
}


?>