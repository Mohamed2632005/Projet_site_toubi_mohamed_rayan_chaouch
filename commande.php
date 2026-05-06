<?php
// ============================================
// C'est la page panier récapitulatif et validation de commande
// ============================================

// Démarrage de la session
session_start();
require 'config/db.php';

// Protection : connexion requise, (on ne peut pas commander sans compte)
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération des articles du panier avec les infos de chaque maillot
// JOIN maillot pour avoir le nom, l'équipe, le prix et le stock de chaque article
// On récupère aussi la personnalisation (nom + numéro) choisie au moment de l'ajout
$stmt = $pdo->prepare('
    SELECT p.id AS panier_id, p.quantite,
           p.personnalisation_nom, p.personnalisation_numero,
           m.id AS maillot_id, m.nom AS maillot_nom, m.equipe,
           m.prix, m.stock
    FROM panier p
    JOIN maillot m ON p.id_maillot = m.id
    WHERE p.id_utilisateur = ?
');
$stmt->execute([$_SESSION['utilisateur_id']]);
$articles = $stmt->fetchAll();

// Si le panier est vide (l'utilisateur arrive ici directement sans passer par panier.php)
if (empty($articles)) {
    header('Location: panier.php');
    exit;
}

// Petit calcul simple du total : somme de (prix × quantité)
$total = 0;
foreach ($articles as $a) {
    $total += $a['prix'] * $a['quantite'];
}

// Drapeaux pour savoir si la commande vient d'être confirmée et pour stocker l'ID de la nouvelle commande
$commande_validee = false;
$id_nouvelle_commande = null;

// Traitement du formulaire de confirmation c'est déclenché quand l'utilisateur clique sur "Confirmer la commande"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Étape 1 : on crée la commande principale avec le montant total
    $stmt = $pdo->prepare('INSERT INTO commande (id_utilisateur, montant_total) VALUES (?, ?)');
    $stmt->execute([$_SESSION['utilisateur_id'], $total]);
    // on utilise lastInsertId() pour récupèrer l'ID auto-incrémenté de la commande qu'on vient de créer
    $id_nouvelle_commande = $pdo->lastInsertId();

    // Étape 2 : on insère chaque article comme une ligne de commande et on décrémente le stock
    foreach ($articles as $a) {
        // Insertion de la ligne de commande avec tous les détails (personnalisation, quantité, prix)
        // On stocke le prix_unitaire, si le prix change plus tard, la commande garde l'ancien prix
        $stmt = $pdo->prepare('
            INSERT INTO commande_ligne
              (id_commande, id_maillot, personnalisation_nom, personnalisation_numero, quantite, prix_unitaire)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $id_nouvelle_commande,
            $a['maillot_id'],
            $a['personnalisation_nom'],
            $a['personnalisation_numero'],
            $a['quantite'],
            $a['prix'],
        ]);

        // Décrémenter le stock du maillot de la quantité commandée
        // stock - quantite : si on commande 2 maillots PSG, le stock baisse de 2
        $stmt = $pdo->prepare('UPDATE maillot SET stock = stock - ? WHERE id = ?');
        $stmt->execute([$a['quantite'], $a['maillot_id']]);
    }

    // Étape 3 : Ici on vide complètement le panier de l'utilisateur après la commande
    $stmt = $pdo->prepare('DELETE FROM panier WHERE id_utilisateur = ?');
    $stmt->execute([$_SESSION['utilisateur_id']]);

    //Ensuite on passe le drapeau à true pour afficher la page de confirmation au lieu du récapitulatif
    $commande_validee = true;
}

// Récupération des infos de l'utilisateur pour afficher l'adresse de livraison
$stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE id = ?');
$stmt->execute([$_SESSION['utilisateur_id']]);
$utilisateur = $stmt->fetch();

$titre = 'Validation commande';
$base_path = '';
include 'includes/header.php';
?>

<?php if ($commande_validee): ?>
    <!-- Confirmation de commande : affiché après validation réussie -->
    <div style="text-align:center; max-width: 540px; margin: 40px auto;">
        <div style="font-size:3.5rem; margin-bottom: 20px;">&#9989;</div>
        <h1 class="page-title">Commande confirmée !</h1>
        <p class="page-subtitle">Merci pour votre commande. Vous recevrez votre maillot dans les meilleurs délais.</p>

        <!-- Récapitulatif de la commande confirmée avec son numéro unique -->
        <div class="panier-resume" style="text-align:left; margin: 28px 0;">
            <h3>Récapitulatif — Commande #<?= $id_nouvelle_commande ?></h3>
            <?php foreach ($articles as $a): ?>
                <div class="resume-ligne">
                    <span><?= htmlspecialchars($a['equipe']) ?> — <?= htmlspecialchars($a['maillot_nom']) ?> x<?= $a['quantite'] ?></span>
                    <span><?= number_format($a['prix'] * $a['quantite'], 2, ',', ' ') ?> €</span>
                </div>
            <?php endforeach; ?>
            <div class="resume-total">
                <span>Total payé</span>
                <span><?= number_format($total, 2, ',', ' ') ?> €</span>
            </div>
        </div>

        <!-- Actions post-commande : voir l'historique ou continuer les achats -->
        <div class="d-flex gap-12" style="justify-content:center;">
            <a href="mon_compte.php" class="btn btn-primary">Voir mes commandes</a>
            <a href="catalogue.php" class="btn btn-ghost">Continuer mes achats</a>
        </div>
    </div>

<?php else: ?>
    <!-- Récapitulatif avant confirmation : l'utilisateur vérifie avant de valider -->
    <h1 class="page-title">Confirmer la commande</h1>
    <p class="page-subtitle">Vérifiez votre récapitulatif avant de valider</p>

    <!-- Mise en page deux colonnes : détail des articles + résumé et bouton de confirmation -->
    <div class="panier-layout">

        <!-- Colonne gauche : tableau des articles + adresse de livraison -->
        <div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Maillot</th>
                            <th>Personnalisation</th>
                            <th>Qté</th>
                            <th>Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a): ?>
                        <tr>
                            <td>
                                <div class="panier-maillot-info">
                                    <div class="equipe"><?= htmlspecialchars($a['equipe']) ?></div>
                                    <div class="nom"><?= htmlspecialchars($a['maillot_nom']) ?></div>
                                </div>
                            </td>
                            <td>
                                <!-- Affichage de la personnalisation si elle a été choisie, sinon tiret -->
                                <?php if ($a['personnalisation_nom'] || $a['personnalisation_numero']): ?>
                                    <span style="font-size:.82rem; color:var(--text-muted);">
                                        <?= htmlspecialchars($a['personnalisation_nom'] ?? '') ?>
                                        <?= $a['personnalisation_numero'] ? ' #' . $a['personnalisation_numero'] : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $a['quantite'] ?></td>
                            <td><strong><?= number_format($a['prix'] * $a['quantite'], 2, ',', ' ') ?> €</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Adresse de livraison : prénom + nom + adresse si renseignée en base -->
            <div class="panier-resume" style="margin-top: 20px;">
                <h3>Livraison</h3>
                <p style="font-size:.9rem; color:var(--text-muted);">
                    <?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?><br>
                    <?php if ($utilisateur['adresse']): ?>
                        <?= htmlspecialchars($utilisateur['adresse']) ?>
                    <?php else: ?>
                        <!-- Message si l'utilisateur n'a pas renseigné d'adresse dans son profil -->
                        <em>Aucune adresse renseignée</em>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Colonne droite : résumé des montants + bouton de confirmation -->
        <div class="panier-resume">
            <h3>Total commande</h3>

            <!-- Récapitulatif ligne par ligne pour vérification avant paiement -->
            <?php foreach ($articles as $a): ?>
                <div class="resume-ligne">
                    <span><?= htmlspecialchars($a['equipe']) ?> x<?= $a['quantite'] ?></span>
                    <span><?= number_format($a['prix'] * $a['quantite'], 2, ',', ' ') ?> €</span>
                </div>
            <?php endforeach; ?>

            <div class="resume-total">
                <span>Total</span>
                <span><?= number_format($total, 2, ',', ' ') ?> €</span>
            </div>

            <!-- Bouton de confirmation : envoie le POST qui déclenche la création de commande en BDD -->
            <form method="POST" action="commande.php">
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Confirmer la commande
                </button>
            </form>
            <!-- Lien retour si l'utilisateur veut modifier son panier avant de confirmer -->
            <a href="panier.php" class="btn btn-ghost btn-block" style="margin-top:10px;">
                Modifier le panier
            </a>
        </div>

    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
