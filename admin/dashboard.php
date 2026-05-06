<?php
// ============================================
// Le tableau de bord de l'admin
// ============================================

session_start(); // Démarrage de la session pour accéder à $_SESSION['utilisateur_id'] et $_SESSION['role']
require '../config/db.php';

// Protection double : l'utilisateur doit être connecté ET avoir le rôle 'admin'
// Si une condition échoue → retour à la page de connexion
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Statistiques générales pour les 4 cartes du dashboard (en gros maillots, commandes, clients, chiffre d'affaires)
$nb_maillots      = $pdo->query('SELECT COUNT(*) FROM maillot')->fetchColumn();
$nb_commandes     = $pdo->query('SELECT COUNT(*) FROM commande')->fetchColumn();
// On ne compte que les utilisateurs normaux, pas les admins
$nb_utilisateurs  = $pdo->query('SELECT COUNT(*) FROM utilisateur WHERE role = "utilisateur"')->fetchColumn();
// COALESCE(SUM(...), 0) va retourner 0 si aucune commande au lieu de NULL
$chiffre_affaires = $pdo->query('SELECT COALESCE(SUM(montant_total), 0) FROM commande')->fetchColumn();

// Les 5 dernières commandes passées, avec le nom et prénom du client
// utilisation de JOIN utilisateur pour avoir les infos du client sans faire une requête supplémentaire
// LIMIT 5 aperçu rapide, pas toute l'historique (la liste complète est dans commandes.php)
$commandes_recentes = $pdo->query('
    SELECT c.*, u.nom, u.prenom
    FROM commande c
    JOIN utilisateur u ON c.id_utilisateur = u.id
    ORDER BY c.date_commande DESC
    LIMIT 5
')->fetchAll();

// on à '../' dans $base_path car ce fichier est dans /admin — les liens CSS/nav doivent remonter d'un niveau
$titre = 'Dashboard Admin';
$base_path = '../';
include '../includes/header.php';
?>

// partie HTML
<div class="d-flex justify-between align-center mb-24">
    <div>
        <h1 class="page-title">Tableau de bord</h1>
        <!-- Nom de l'admin connecté récupéré depuis la session -->
        <p class="page-subtitle">Bienvenue, <?= htmlspecialchars($_SESSION['utilisateur_nom']) ?></p>
    </div>
</div>

<!-- Liens de navigation entre les pages admin -->
<div class="admin-nav">
    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
    <a href="maillots.php"  class="btn btn-outline">Gérer les maillots</a>
    <a href="commandes.php" class="btn btn-outline">Gérer les commandes</a>
    <a href="../catalogue.php" class="btn btn-ghost">Voir le site</a>
</div>

<!-- 4 cartes de statistiques : maillots, commandes, clients, chiffre d'affaires -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Maillots en catalogue</div>
        <div class="stat-value"><?= $nb_maillots ?></div>
        <div class="stat-unit">produits disponibles</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Commandes totales</div>
        <div class="stat-value"><?= $nb_commandes ?></div>
        <div class="stat-unit">depuis le lancement</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Clients inscrits</div>
        <div class="stat-value"><?= $nb_utilisateurs ?></div>
        <div class="stat-unit">comptes utilisateurs</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Chiffre d'affaires</div>
        <!-- number_format sans décimales pour l'affichage du CA (plus lisible) -->
        <div class="stat-value"><?= number_format($chiffre_affaires, 0, ',', ' ') ?></div>
        <div class="stat-unit">euros de revenus</div>
    </div>
</div>

<!-- Tableau des 5 dernières commandes avec lien vers la gestion complète -->
<h2 class="section-title">Dernières commandes</h2>

<?php if (empty($commandes_recentes)): ?>
    <div class="empty-state">
        <p>Aucune commande pour l'instant.</p>
    </div>
<?php else: ?>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes_recentes as $cmd):
                    // Badge de couleur selon le statut de livraison
                    $badge_class = match($cmd['statut']) {
                        'livré'    => 'badge-green',
                        'expédié'  => 'badge-yellow',
                        'annulé'   => 'badge-red',
                        default    => 'badge-gray', // 'en cours' ou autre valeur inattendue
                    };
                ?>
                <tr>
                    <td><strong>#<?= $cmd['id'] ?></strong></td>
                    <td><?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?></td>
                    <!-- date() formate la date SQL en format français jj/mm/AAAA -->
                    <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
                    <td><?= number_format($cmd['montant_total'], 2, ',', ' ') ?> €</td>
                    <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cmd['statut']) ?></span></td>
                    <!-- Lien vers la page de gestion complète des commandes -->
                    <td><a href="commandes.php" class="btn btn-ghost btn-sm">Détails</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
