<?php
/**
 * traitement de l'ajout d'un avis  
 */


require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérification de l'authentification 

requireLogin();

// Récupération de l'action (ajouter ou supprimer)
$action = $_POST['action'] ?? $_GET['action'] ?? 'add';

// GESTION DE LA SUPPRESSION
if ($action === 'delete') {
    // Vérification de la méthode GET ou POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        redirect('pages/reservation.php');
    }
    
    $id_avis = (int)($_POST['id_avis'] ?? $_GET['id_avis'] ?? 0);
    $id_produit = (int)($_POST['id_produit'] ?? $_GET['id_produit'] ?? 0);
    
    if ($id_avis <= 0) {
        $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
        redirect($redirect_url, 'Avis invalide.');
    }
    
    $db = getDB();
    $id_membre = $_SESSION['membre']['id_membre'];
    
    // Vérifier que l'avis existe et que l'utilisateur est l'auteur ou admin
    $stmt = $db->prepare("SELECT id_avis, id_membre, id_salle FROM avis WHERE id_avis = ?");
    $stmt->execute([$id_avis]);
    $avis = $stmt->fetch();
    
    if (!$avis) {
        $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
        redirect($redirect_url, 'Avis introuvable.');
    }
    
    // Vérifier que l'utilisateur est l'auteur de l'avis ou qu'il est admin
    if ($avis['id_membre'] != $id_membre && !isAdmin()) {
        $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
        redirect($redirect_url, 'Vous n\'avez pas le droit de supprimer cet avis.');
    }
    
    // Suppression de l'avis
    try {
        $stmt = $db->prepare("DELETE FROM avis WHERE id_avis = ?");
        $stmt->execute([$id_avis]);
        
        $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
        redirect($redirect_url, 'Votre avis a été supprimé avec succès !');
    } catch (PDOException $e) {
        $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
        redirect($redirect_url, 'Erreur lors de la suppression de l\'avis.');
    }
    
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/reservation.php');

}

$id_salle = (int)($_POST['id_salle'] ?? 0);
$id_produit = (int)($_POST['id_produit'] ?? 0);
$note = (int)($_POST['note'] ?? 0);
$commentaire = cleanInput($_POST['commentaire'] ?? '');

$errors = [];

if ( $id_salle <= 0) {
    $errors[] = " salle invalide ";
}

if ( $note < 1 || $note > 5) {
    $errors[] = " La note doit être comprise entre 1 et 5 ";

}

// Vérifier que le membre n'a pas déjà laissé un avis pour cette salle
if (empty($errors)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id_avis FROM avis WHERE id_salle = ? AND id_membre = ?");
    $stmt->execute([$id_salle, $_SESSION['membre']['id_membre']]);
    if ($stmt->fetch()) {
        $errors[] = "Vous avez déjà laissé un avis pour cette salle.";
    }
}

if (empty($commentaire) || strlen($commentaire) < 10) {
    $errors[] = "Le commentaire doit contenir au moins 10 caractères.";
}

// Vérifier que la salle existe
if (empty($errors)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id_salle FROM salle WHERE id_salle = ?");
    $stmt->execute([$id_salle]);
    if (!$stmt->fetch()) {
        $errors[] = "Salle introuvable.";
    }
}

// Insertion de l'avis
if (empty($errors)) {
    $db = getDB();
    $id_membre = $_SESSION['membre']['id_membre'];
    
    try {
        // La note dans la base est stockée de 1 à 5, pas 1 à 10
        $stmt = $db->prepare("
            INSERT INTO avis (id_membre, id_salle, commentaire, note, date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$id_membre, $id_salle, $commentaire, $note]);
        
        // Redirection vers reservation_details.php avec id_produit
        $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
        redirect($redirect_url, 'Votre avis a été publié avec succès !');
    } catch (PDOException $e) {
        $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
        redirect($redirect_url, 'Erreur lors de la publication de l\'avis.');
    }
} else {
    $message = implode(' ', $errors);
    $redirect_url = $id_produit ? 'pages/reservation_details.php?id_produit=' . $id_produit : 'pages/reservation.php';
    redirect($redirect_url, $message);
}

?>