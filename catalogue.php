<?php
// ============================================
// La page de catalogue des maillots (accessible à tous les utilisateurs, même non connectés)
// ============================================

session_start(); // Démarrage de la session pour accéder à $_SESSION['utilisateur_id']
require 'config/db.php';

// Protection : connexion requise pour accéder au catalogue
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// Lecture du filtre d'équipe passé dans l'URL — ex: catalogue.php?equipe=PSG
// trim() pour nettoyer les espaces éventuels
$equipe_filtre = trim($_GET['equipe'] ?? '');

// Si un filtre est actif, on ne récupère que les maillots de cette équipe
// Sinon, on récupère tous les maillots disponibles
// ORDER BY equipe, nom. En gros tri alphabétique d'abord par équipe, puis par nom de maillot
if ($equipe_filtre !== '') {
    $stmt = $pdo->prepare('SELECT * FROM maillot WHERE equipe = ? AND stock > 0 ORDER BY equipe, nom');
    $stmt->execute([$equipe_filtre]);
} else {
    $stmt = $pdo->query('SELECT * FROM maillot WHERE stock > 0 ORDER BY equipe, nom');
}
$maillots = $stmt->fetchAll();

// Ici nn récupère la liste distincte des équipes pour construire les boutons de filtre
// DISTINCT nous pérmet éviter les doublons (une équipe avec 3 maillots n'apparaît qu'une fois)
// FETCH_COLUMN(0) pérmet de retourner un simple tableau de strings au lieu d'un tableau de tableaux
$equipes = $pdo->query('SELECT DISTINCT equipe FROM maillot ORDER BY equipe')->fetchAll(PDO::FETCH_COLUMN);

$titre = 'Catalogue';
$base_path = '';
include 'includes/header.php';
?>

<div class="d-flex justify-between align-center mb-24" style="flex-wrap:wrap; gap:12px;">
    <div>
        <h1 class="page-title">Catalogue</h1>
        <!-- Affiche le nombre de maillots trouvés avec accord automatique du pluriel -->
        <p class="page-subtitle"><?= count($maillots) ?> maillot<?= count($maillots) > 1 ? 's' : '' ?> disponible<?= count($maillots) > 1 ? 's' : '' ?></p>
    </div>
</div>

<!-- Barre de filtres : boutons pour filtrer par équipe -->
<div class="filtre-bar">
    <!-- "Toutes les équipes" est actif quand aucun filtre n'est sélectionné -->
    <a href="catalogue.php" class="<?= $equipe_filtre === '' ? 'actif' : '' ?>">Toutes les équipes</a>
    <?php foreach ($equipes as $eq): ?>
        <!-- urlencode() encode les caractères spéciaux dans l'URL (ex: "Saint-Étienne" → "Saint-%C3%89tienne") -->
        <a href="catalogue.php?equipe=<?= urlencode($eq) ?>"
           class="<?= $equipe_filtre === $eq ? 'actif' : '' ?>">
            <?= htmlspecialchars($eq) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Grille des maillots ou message si aucun résultat -->
<?php if (empty($maillots)): ?>
    <!-- État vide : aucun maillot pour l'équipe filtrée -->
    <div class="empty-state">
        <div class="empty-icon">&#9917;</div>
        <p>Aucun maillot disponible pour cette équipe.</p>
        <a href="catalogue.php" class="btn btn-primary">Voir tout le catalogue</a>
    </div>
<?php else: ?>
    <div class="maillots-grid">
        <?php foreach ($maillots as $m): ?>
            <div class="maillot-card">
                <!-- onerror : image de secours si l'URL du maillot est cassée ou vide -->
                <img src="<?= htmlspecialchars($m['image_url'] ?? '') ?>"
                     alt="<?= htmlspecialchars($m['nom']) ?>"
                     onerror="this.src='https://placehold.co/400x500/1a1a24/00c875?text=FootStyle'">
                <div class="maillot-card-body">
                    <div class="maillot-card-equipe"><?= htmlspecialchars($m['equipe']) ?></div>
                    <div class="maillot-card-nom"><?= htmlspecialchars($m['nom']) ?></div>
                    <!-- Description tronquée à 70 caractères pour ne pas casser la mise en page -->
                    <?php if ($m['description']): ?>
                        <div class="text-muted" style="font-size:.78rem; margin-bottom:10px; line-height:1.4;">
                            <?= htmlspecialchars(mb_substr($m['description'], 0, 70)) ?>…
                        </div>
                    <?php endif; ?>
                    <div class="maillot-card-footer">
                        <!-- number_format(prix, 2, ',', ' ') → affiche le prix en format français : 89,99 € -->
                        <span class="maillot-card-prix"><?= number_format($m['prix'], 2, ',', ' ') ?> €</span>
                        <!-- Bouton "Personnaliser" → redirige vers la fiche détaillée du maillot -->
                        <a href="maillot.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">Personnaliser</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
