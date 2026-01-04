<?php
/**
 * Page panier (panier.php)
 * Zone 3 : Tableau du panier avec actions
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérification de l'authentification (seulement membre ou admin)
if (!isLoggedIn()) {
    redirect('auth/connexion.php', 'Vous devez être connecté pour accéder au panier.');
}

$db = getDB();
$errors = [];
$success = '';

// Initialisation du panier en session
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Traitement des actions GET (pour "Vider mon panier" et retirer avec lien)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'clear') {
        $_SESSION['panier'] = [];
        $success = 'Panier vidé.';
    } elseif ($action === 'remove') {
        $id_produit = (int)($_GET['id_produit'] ?? 0);
        foreach ($_SESSION['panier'] as $key => $item) {
            if ($item['id_produit'] == $id_produit) {
                unset($_SESSION['panier'][$key]);
                $_SESSION['panier'] = array_values($_SESSION['panier']);
                $success = 'Produit retiré du panier.';
                break;
            }
        }
    }
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $id_produit = (int)($_POST['id_produit'] ?? 0);
        if ($id_produit > 0) {
            // Vérifier que le produit existe et est disponible (etat = 0 = disponible selon cahier)
            $stmt = $db->prepare("
                SELECT p.*, s.titre as salle_titre, s.ville, s.capacite, s.photo, pr.code_promo, pr.reduction
                FROM produit p
                JOIN salle s ON p.id_salle = s.id_salle
                LEFT JOIN promotion pr ON p.id_promo = pr.id_promo
                WHERE p.id_produit = ? AND p.etat = 0
            ");
            $stmt->execute([$id_produit]);
            $produit = $stmt->fetch();
            
            if ($produit) {
                $deja_present = false;
                foreach ($_SESSION['panier'] as $item) {
                    if ($item['id_produit'] == $id_produit) {
                        $deja_present = true;
                        break;
                    }
                }
                
                if (!$deja_present) {
                    $prix_ht = $produit['prix'];
                    if ($produit['reduction']) {
                        $prix_ht = calculatePriceWithDiscount($produit['prix'], $produit['reduction']);
                    }
                    
                    $_SESSION['panier'][] = [
                        'id_produit' => $id_produit,
                        'salle_titre' => $produit['salle_titre'],
                        'ville' => $produit['ville'],
                        'capacite' => $produit['capacite'],
                        'photo' => $produit['photo'],
                        'date_arrivee' => $produit['date_arrivee'],
                        'date_depart' => $produit['date_depart'],
                        'prix_ht' => $prix_ht,
                        'prix_original' => $produit['prix'],
                        'id_promo' => $produit['id_promo'],
                        'reduction' => $produit['reduction'] ?? 0
                    ];
                    
                    redirect('actions/panier.php', 'Produit ajouté au panier avec succès !');
                } else {
                    $errors[] = 'Ce produit est déjà dans votre panier.';
                }
            } else {
                $errors[] = 'Produit introuvable ou non disponible.';
            }
        }
    } elseif ($action === 'apply_promo') {
        // Appliquer un code promo
        $code_promo = cleanInput($_POST['code_promo'] ?? '');
        if (!empty($code_promo)) {
            $stmt = $db->prepare("SELECT * FROM promotion WHERE code_promo = ?");
            $stmt->execute([$code_promo]);
            $promo = $stmt->fetch();
            
            if ($promo) {
                // Vérifier si le code promo est associé aux produits du panier
                $promo_appliquee = false;
                foreach ($_SESSION['panier'] as $key => $item) {
                    if ($item['id_promo'] == $promo['id_promo']) {
                        // Le code promo est déjà associé à ce produit
                        $promo_appliquee = true;
                        break;
                    }
                }
                
                if (!$promo_appliquee) {
                    $errors[] = "Ce code promo n'est pas valide pour les produits de votre panier.";
                } else {
                    // Recalculer les prix avec la réduction (déjà fait lors de l'ajout)
                    $success = 'Code promo appliqué !';
                }
            } else {
                $errors[] = "Code promo invalide.";
            }
        }
        // Recharger la page pour afficher les réductions
    } elseif ($action === 'payer') {
        // Passer la commande
        if (empty($_SESSION['panier'])) {
            $errors[] = 'Votre panier est vide.';
        } elseif (!isset($_POST['accept_cgv']) || $_POST['accept_cgv'] != '1') {
            $errors[] = 'Vous devez accepter les conditions générales de vente.';
        } else {
            // Calcul du montant total HT
            $montant_ht = 0;
            foreach ($_SESSION['panier'] as $item) {
                $montant_ht += $item['prix_ht'];
            }
            
            // TVA 20%
            $tva = $montant_ht * 0.20;
            $montant_ttc = $montant_ht + $tva;
            
            // Vérifier que tous les produits sont encore disponibles (etat = 0)
            $produits_valides = true;
            foreach ($_SESSION['panier'] as $item) {
                $stmt = $db->prepare("SELECT etat FROM produit WHERE id_produit = ? AND etat = 0");
                $stmt->execute([$item['id_produit']]);
                if (!$stmt->fetch()) {
                    $produits_valides = false;
                    $errors[] = 'Le produit "' . $item['salle_titre'] . '" n\'est plus disponible.';
                }
            }
            
            if ($produits_valides && empty($errors)) {
                try {
                    $db->beginTransaction();
                    
                    // Créer la commande (montant en centimes)
                    $id_membre = $_SESSION['membre']['id_membre'];
                    $stmt = $db->prepare("
                        INSERT INTO commande (montant, id_membre, date)
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$montant_ttc]); // Montant en centimes
                    $id_commande = $db->lastInsertId();
                    
                    // Créer les détails de commande et marquer les produits comme réservés (etat = 1)
                    foreach ($_SESSION['panier'] as $item) {
                        // Détail de commande
                        $stmt = $db->prepare("
                            INSERT INTO details_commande (id_commande, id_produit)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$id_commande, $item['id_produit']]);
                        
                        // Marquer le produit comme réservé (etat = 1)
                        $stmt = $db->prepare("UPDATE produit SET etat = 1 WHERE id_produit = ?");
                        $stmt->execute([$item['id_produit']]);
                    }
                    
                    $db->commit();
                    
                    // Vider le panier
                    $_SESSION['panier'] = [];
                    
                    // TODO: Envoyer email de confirmation avec PDF facture
                    // redirect('pages/profil.php', 'Commande validée avec succès ! Numéro de commande : #' . $id_commande);
                    
                    redirect('pages/profil.php', 'Commande validée avec succès ! Numéro de commande : #' . $id_commande);
                } catch (PDOException $e) {
                    $db->rollBack();
                    $errors[] = 'Erreur lors de la validation de la commande.';
                    error_log("Erreur commande: " . $e->getMessage());
                }
            }
        }
    }
}

// Charger les détails complets des produits du panier
$panier_details = [];
foreach ($_SESSION['panier'] as $item) {
    $stmt = $db->prepare("
        SELECT p.*, s.titre as salle_titre, s.ville, s.capacite, s.photo
        FROM produit p
        JOIN salle s ON p.id_salle = s.id_salle
        WHERE p.id_produit = ?
    ");
    $stmt->execute([$item['id_produit']]);
    $produit = $stmt->fetch();
    
    if ($produit) {
        $panier_details[] = array_merge($item, $produit);
    }
}

// Calcul des totaux
$total_ht = 0;
$total_tva = 0;
$total_ttc = 0;

foreach ($panier_details as $item) {
    $total_ht += $item['prix_ht'];
}
$total_tva = $total_ht * 0.20; // TVA 20%
$total_ttc = $total_ht + $total_tva;

$pageTitle = 'Mon panier - LOKISALLE';
$pageCSS = 'panier.css';

include '../includes/haut.inc.php';
include '../includes/menu.inc.php';
?>

<div class="container">
    <!-- Breadcrumb Zone 2 -->
    <div class="breadcrumb">
        <p>&gt;&gt; Panier</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="success-message">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($panier_details)): ?>
        <div class="panier-vide">
            <p>Votre panier est vide.</p>
            <p><a href="<?php echo SITE_URL; ?>/pages/reservation.php" class="btn">Consulter les offres</a></p>
        </div>
    <?php else: ?>
        <!-- Zone 3 : Tableau du panier -->
        <div class="panier-table-container">
            <h2>Votre panier</h2>
            
            <table class="panier-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Salle</th>
                        <th>Photo</th>
                        <th>Ville</th>
                        <th>Capacité</th>
                        <th>Date Arrivée</th>
                        <th>Date Départ</th>
                        <th>Retirer</th>
                        <th>Prix HT</th>
                        <th>TVA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($panier_details as $item): ?>
                        <?php 
                        $date_arr = new DateTime($item['date_arrivee']);
                        $date_dep = new DateTime($item['date_depart']);
                        $tva_item = $item['prix_ht'] * 0.20;
                        ?>
                        <tr>
                            <td><?php echo $item['id_produit']; ?></td>
                            <td><?php echo htmlspecialchars($item['salle_titre']); ?></td>
                            <td>
                                <?php if (!empty($item['photo'])): ?>
                                    <img src="<?php echo UPLOAD_URL . 'salles/' . htmlspecialchars($item['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['salle_titre']); ?>" 
                                         class="produit-photo"
                                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                                <?php else: ?>
                                    <span class="photo-placeholder">photo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['ville']); ?></td>
                            <td><?php echo $item['capacite']; ?></td>
                            <td><?php 
                            $mois_fr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                            echo $date_arr->format('d') . ' ' . $mois_fr[(int)$date_arr->format('n') - 1] . ' ' . $date_arr->format('Y'); 
                            ?></td>
                            <td><?php 
                            echo $date_dep->format('d') . ' ' . $mois_fr[(int)$date_dep->format('n') - 1] . ' ' . $date_dep->format('Y'); 
                            ?></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/actions/panier.php?action=remove&id_produit=<?php echo $item['id_produit']; ?>" class="link-retirer">x</a>
                            </td>
                            <td><?php echo number_format($item['prix_ht'] / 100, 0, ',', ' '); ?>€</td>
                            <td>20%</td>
                        </tr>
                        <?php if ($item['reduction'] > 0): ?>
                            <tr class="reduction-info">
                                <td colspan="9" class="text-right">
                                    <strong>Réduction appliquée : <?php echo $item['reduction']; ?>% 
                                    (Économie : <?php echo number_format(($item['prix_original'] - $item['prix_ht']) / 100, 2, ',', ' '); ?>€)</strong>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="8" class="text-right"><strong>Prix Total TTC:</strong></td>
                        <td colspan="2"><strong><?php echo number_format($total_ttc / 100, 2, ',', ' '); ?> €</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Zone 3 : Actions et paiement -->
            <div class="panier-actions">
                <div class="cgv-checkbox">
                    <label>
                        <input type="checkbox" name="accept_cgv" value="1" id="cgv-checkbox" required>
                        J'accepte les conditions générales de vente (<a href="<?php echo SITE_URL; ?>/pages/cgv.php" target="_blank">voir</a>)
                    </label>
                </div>
                
                <div class="code-promo-section">
                    <form method="POST" action="" class="code-promo-form">
                        <input type="hidden" name="action" value="apply_promo">
                        <label for="code_promo">Utiliser un code promo:</label>
                        <input type="text" id="code_promo" name="code_promo" placeholder="Entrez votre code" value="<?php echo htmlspecialchars($_POST['code_promo'] ?? ''); ?>">
                        <button type="submit" class="btn-apply-promo">Appliquer</button>
                    </form>
                </div>
                
                <div class="payer-section">
                    <form method="POST" action="" class="payer-form" id="payer-form">
                        <input type="hidden" name="action" value="payer">
                        <input type="hidden" name="accept_cgv" value="0" id="cgv-input">
                        <button type="submit" class="btn-payer" id="btn-payer">Payer</button>
                    </form>
                </div>
                
                <div class="vider-panier-link">
                    <a href="<?php echo SITE_URL; ?>/actions/panier.php?action=clear">+ Vider mon panier</a>
                </div>
            </div>
            
            <div class="info-paiement">
                <p><strong>Tous nos articles sont calculés avec le taux de TVA à 20%</strong></p>
                <p><strong>Règlement: Par Chèque uniquement</strong></p>
                <p>Ma boutique - 1 Rue Boswellia, 75000 Paris, France</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Gérer la case CGV pour le formulaire de paiement
document.addEventListener('DOMContentLoaded', function() {
    const cgvCheckbox = document.getElementById('cgv-checkbox');
    const cgvInput = document.getElementById('cgv-input');
    const payerForm = document.getElementById('payer-form');
    const btnPayer = document.getElementById('btn-payer');
    
    if (cgvCheckbox && cgvInput && payerForm) {
        cgvCheckbox.addEventListener('change', function() {
            cgvInput.value = this.checked ? '1' : '0';
        });
        
        payerForm.addEventListener('submit', function(e) {
            if (!cgvCheckbox.checked) {
                e.preventDefault();
                alert('Vous devez accepter les conditions générales de vente pour procéder au paiement.');
                return false;
            }
        });
    }
});
</script>

<?php include '../includes/bas.inc.php'; ?>