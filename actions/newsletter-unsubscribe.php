<?php
/**
 * Traitement du désabonnement à la newsletter
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/newsletter.php');
}

$id_membre = $_SESSION['membre']['id_membre'];
$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM newsletter WHERE id_membre = ?");
    $stmt->execute([$id_membre]);
    redirect('pages/newsletter.php', 'Vous êtes maintenant désabonné de la newsletter.');
} catch (PDOException $e) {
    redirect('pages/newsletter.php', 'Erreur lors du désabonnement.');
}