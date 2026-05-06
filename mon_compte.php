<?php
// ============================================
// Profil et historique des commandes de chaque utilisateur (connexion requise)
// ============================================

session_start(); // Démarrage de la session pour accéder à $_SESSION['utilisateur_id']
require 'config/db.php';

// Protection : connexion requise pour accéder à son compte
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// On récupération des informations du profil de l'utilisateur connecté
$stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE id = ?');
$stmt->execute([$_SESSION['utilisateur_id']]);
$utilisateur = $stmt->fetch();

// On récupération de toutes les commandes de l'utilisateur, de la plus récente à la plus ancienne
$stmt = $pdo->prepare('
    SELECT * FROM commande
    WHERE id_utilisateur = ?
    ORDER BY date_commande DESC
');
$stmt->execute([$_SESSION['utilisateur_id']]);
$commandes = $stmt->fetchAll();

// Pour chaque commande, on va chercher le détail des articles commandés (commande_ligne)
// Ici on stocke les lignes dans un tableau indexé par l'ID de commande pour faciliter l'affichage
$lignes_par_commande = [];
foreach ($commandes as $cmd) {
    $stmt = $pdo->prepare('
        SELECT cl.*, m.nom AS maillot_nom, m.equipe
        FROM commande_ligne cl
        JOIN maillot m ON cl.id_maillot = m.id
        WHERE cl.id_commande = ?
    ');
    $stmt->execute([$cmd['id']]);
    // Indexé par ID de commande : $lignes_par_commande[42] = [...lignes de la commande 42...]
    $lignes_par_commande[$cmd['id']] = $stmt->fetchAll();
}

// On extrait la première lettre du prénom pour faire l'avatar (je trouvais sa stylée et pro)
// mb_substr() est la version multi-octets de substr() — gère correctement les accents (É, À, etc.)
$initiale = strtoupper(mb_substr($utilisateur['prenom'], 0, 1));

// Affichage de la page
$titre = 'Mon compte';
$base_path = '';
include 'includes/header.php';
?>

// partie HTML
<!-- En-tête du profil : avatar, nom, email, rôle et bouton déconnexion -->
<div class="compte-header">
    <!-- Avatar généré dynamiquement avec la première lettre du prénom -->
    <div class="compte-avatar"><?= $initiale ?></div>
    <div>
        <div class="compte-nom">
            <?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?>
        </div>
        <div class="compte-email"><?= htmlspecialchars($utilisateur['email']) ?></div>
        <!-- Badge de rôle : vert pour admin, gris pour utilisateur normal -->
        <div class="compte-role">
            <?php if ($utilisateur['role'] === 'admin'): ?>
                <span class="badge badge-green">Administrateur</span>
            <?php else: ?>
                <span class="badge badge-gray">Utilisateur</span>
            <?php endif; ?>
        </div>
    </div>
    <!-- Bouton de déconnexion poussé à droite avec margin-left: auto -->
    <div style="margin-left: auto;">
        <a href="logout.php" class="btn btn-danger btn-sm">Déconnexion</a>
    </div>
</div>

<!-- Historique de toutes les commandes passées par l'utilisateur -->
<h2 class="section-title">Mes commandes</h2>

<!-- État vide si l'utilisateur n'a jamais commandé -->
<?php if (empty($commandes)): ?>
    <div class="empty-state">
        <div class="empty-icon">&#128722;</div>
        <p>Vous n'avez pas encore passé de commande.</p>
        <a href="catalogue.php" class="btn btn-primary">Voir le catalogue</a>
    </div>
<?php else: ?>

    <?php foreach ($commandes as $cmd): ?>
        <?php
        // match() choisit la classe CSS du badge selon le statut de la commande
        // badge-green = livré, badge-yellow = expédié, badge-red = annulé, badge-gray = en cours
        $badge_class = match($cmd['statut']) {
            'livré'    => 'badge-green',
            'expédié'  => 'badge-yellow',
            'annulé'   => 'badge-red',
            default    => 'badge-gray',
        };
        ?>
        <!-- Carte d'une commande : en-tête avec ID, date, statut et total -->
        <div class="commande-item">
            <div class="commande-item-header">
                <div>
                    <span class="commande-id">Commande #<?= $cmd['id'] ?></span>
                    <!-- strtotime() convertit la date SQL en timestamp Unix, date() la reformate en français -->
                    <div class="commande-date">
                        <?= date('d/m/Y à H:i', strtotime($cmd['date_commande'])) ?>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cmd['statut']) ?></span>
                    <span class="commande-total"><?= number_format($cmd['montant_total'], 2, ',', ' ') ?> €</span>
                </div>
            </div>

            <!-- Détail des articles de la commande (récupérés plus haut dans $lignes_par_commande) -->
            <?php if (!empty($lignes_par_commande[$cmd['id']])): ?>
                <div class="commande-lignes">
                    <?php foreach ($lignes_par_commande[$cmd['id']] as $ligne): ?>
                        <span style="margin-right: 12px;">
                            &bull; <?= htmlspecialchars($ligne['equipe']) ?> — <?= htmlspecialchars($ligne['maillot_nom']) ?>
                            x<?= $ligne['quantite'] ?>
                            <!-- Affichage de la personnalisation si elle existe pour cette ligne -->
                            <?php if ($ligne['personnalisation_nom']): ?>
                                (<?= htmlspecialchars($ligne['personnalisation_nom']) ?>
                                <?= $ligne['personnalisation_numero'] ? ' #' . $ligne['personnalisation_numero'] : '' ?>)
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
