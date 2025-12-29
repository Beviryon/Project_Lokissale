<?php

require_once __DIR__ . '/database.php'

 /**
  * Vérifie si un utilisateur est connecté 
  */

 function isloggedIn() {
    return isset($_SESSION['membre']['statut']) && $_SESSION['membre']['statut'] == 1;
 }
?>