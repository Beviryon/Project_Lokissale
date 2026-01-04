<?php
/**
 * Page de déconnexion
 * Déconnecte l'utilisateur et le redirige
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Destruction de la session
session_destroy();

// Redirection vers l'accueil
redirect('index.php', 'Vous avez été déconnecté avec succès.');