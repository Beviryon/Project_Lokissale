<?php
/**
 * Traitement du désabonnement à la newsletter
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$id_membre = $_SESSION['membre']['id_membre'];
$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM newsletter WHERE id_membre = ?");
    $stmt->execute([$id_membre]);
    redirect('actions/newsletter-subscribe.php', 'Vous êtes maintenant désabonné de la newsletter.');
} catch (PDOException $e) {
    redirect('actions/newsletter-subscribe.php', 'Erreur lors du désabonnement.');
}