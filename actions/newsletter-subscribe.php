<?php
/**
 * Traitement de l'abonnement à la newsletter
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$id_membre = $_SESSION['membre']['id_membre'];
$db = getDB();

// Vérifier si déjà abonné
$stmt = $db->prepare("SELECT * FROM newsletter WHERE id_membre = ?");
$stmt->execute([$id_membre]);

if ($stmt->fetch()) {
    redirect('pages/newsletter.php', 'Vous êtes déjà abonné à la newsletter.');
}

// Ajouter l'abonnement
try {
    $stmt = $db->prepare("INSERT INTO newsletter (id_membre, date_inscription) VALUES (?, NOW())");
    $stmt->execute([$id_membre]);
    redirect('pages/newsletter.php', 'Vous êtes maintenant abonné à la newsletter !');
} catch (PDOException $e) {
    redirect('pages/newsletter.php', 'Erreur lors de l\'abonnement.');
}