<?php
// ============================================
// Affichage du panier
// ============================================

session_start(); // Démarrage de la session pour accéder à $_SESSION['utilisateur_id']
require 'config/db.php';

// Protection : connexion requise pour accéder au panier
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// Ici on fait la suppression d'un article du panier via ?supprimer=ID dans l'URL
if (isset($_GET['supprimer'])) {
    $id_panier = intval($_GET['supprimer']);
    // On filtre aussi par id_utilisateur parce que si non un utilisateur pourait
    // supprimer les articles du panier d'un autre utilisateur en devinant l'ID
    $stmt = $pdo->prepare('DELETE FROM panier WHERE id = ? AND id_utilisateur = ?');
    $stmt->execute([$id_panier, $_SESSION['utilisateur_id']]);
    // Redirection pour éviter de re-supprimer si l'utilisateur rafraîchit la page (pattern PRG)
    header('Location: panier.php');
    exit;
}

// Ici on fait la récupération de tous les articles du panier avec les infos des maillots associés
// JOIN maillot pour avoir le nom, l'équipe, le prix et l'image de chaque article
// ORDER BY p.id DESC affiche les articles du plus récent au plus ancien
$stmt = $pdo->prepare('
    SELECT p.id AS panier_id, p.quantite,
           p.personnalisation_nom, p.personnalisation_numero,
           m.id AS maillot_id, m.nom AS maillot_nom, m.equipe,
           m.prix, m.image_url
    FROM panier p
    JOIN maillot m ON p.id_maillot = m.id
    WHERE p.id_utilisateur = ?
    ORDER BY p.id DESC
');
$stmt->execute([$_SESSION['utilisateur_id']]);
$articles = $stmt->fetchAll();

// Simple calcul du total : somme de (prix × quantité) pour chaque article du panier
$total = 0;
foreach ($articles as $a) {
    $total += $a['prix'] * $a['quantite'];
}

// Affichage de la page
$titre = 'Mon panier';
$base_path = '';
include 'includes/header.php';
?>

// partie HTML
// En-tête avec le titre de la page et le nombre d'articles dans le panier
<h1 class="page-title">Mon panier</h1>
<p class="page-subtitle">
    <!-- Accord automatique du pluriel : "1 article" ou "3 articles" -->
    <?= count($articles) ?> article<?= count($articles) > 1 ? 's' : '' ?> dans votre panier
</p>

<!-- État vide : message et lien vers le catalogue si le panier ne contient rien -->
<?php if (empty($articles)): ?>
    <div class="empty-state">
        <div class="empty-icon">&#128722;</div>
        <p>Votre panier est vide.</p>
        <a href="catalogue.php" class="btn btn-primary">Voir le catalogue</a>
    </div>
<?php else: ?>

<!-- Mise en page deux colonnes : tableau des articles + résumé de commande -->
<div class="panier-layout">

    <!-- Tableau listant tous les articles du panier -->
    <div class="panier-table">
        <table>
            <thead>
                <tr>
                    <th>Maillot</th>
                    <th>Personnalisation</th>
                    <th>Prix unit.</th>
                    <th>Qté</th>
                    <th>Sous-total</th>
                    <th></th>
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
                        <div class="panier-maillot-info">
                            <!-- Affichage conditionnel de la personnalisation si elle existe -->
                            <?php if ($a['personnalisation_nom']): ?>
                                <div class="perso">Nom : <?= htmlspecialchars($a['personnalisation_nom']) ?></div>
                            <?php endif; ?>
                            <?php if ($a['personnalisation_numero']): ?>
                                <div class="perso">N° : <?= $a['personnalisation_numero'] ?></div>
                            <?php endif; ?>
                            <!-- Si aucune personnalisation n'a été choisie, on affiche "Aucune" -->
                            <?php if (!$a['personnalisation_nom'] && !$a['personnalisation_numero']): ?>
                                <span class="text-muted" style="font-size:.8rem;">Aucune</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= number_format($a['prix'], 2, ',', ' ') ?> €</td>
                    <td><?= $a['quantite'] ?></td>
                    <!-- Sous-total de la ligne = prix unitaire × quantité -->
                    <td><strong><?= number_format($a['prix'] * $a['quantite'], 2, ',', ' ') ?> €</strong></td>
                    <td>
                        <!-- Bouton de suppression avec confirmation JavaScript avant d'envoyer la requête -->
                        <a href="panier.php?supprimer=<?= $a['panier_id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Retirer cet article ?')">
                            Retirer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bloc résumé : récapitulatif des montants et bouton de validation -->
    <div class="panier-resume">
        <h3>Résumé</h3>

        <!-- Liste des lignes de commande avec sous-total par équipe -->
        <?php foreach ($articles as $a): ?>
            <div class="resume-ligne">
                <span><?= htmlspecialchars($a['equipe']) ?> x<?= $a['quantite'] ?></span>
                <span><?= number_format($a['prix'] * $a['quantite'], 2, ',', ' ') ?> €</span>
            </div>
        <?php endforeach; ?>

        <!-- Total général en bas du résumé -->
        <div class="resume-total">
            <span>Total</span>
            <span><?= number_format($total, 2, ',', ' ') ?> €</span>
        </div>

        <!-- Bouton principal : redirige vers la page de confirmation de commande -->
        <a href="commande.php" class="btn btn-primary btn-block btn-lg">
            Valider la commande
        </a>
        <a href="catalogue.php" class="btn btn-ghost btn-block mt-8" style="margin-top:10px;">
            Continuer les achats
        </a>
    </div>

</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
