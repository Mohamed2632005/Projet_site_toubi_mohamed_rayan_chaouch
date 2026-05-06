<?php
// ============================================
// (La page principale) Page d'accueil
// ============================================

// Démarrage de la session afficher le nom de l'utilisateur si connecté
session_start();
require 'config/db.php';

// Titre de la page et chemin de base pour header.php
$titre = 'Accueil';
$base_path = '';

// Récupère les 6 derniers maillots ajoutés pour les afficher en vitrine sur la page d'accueil
// ORDER BY id DESC → du plus récent au plus ancien (le dernier ajouté apparaît en premier)
// LIMIT 6 → on n'affiche que 6 cartes pour ne pas surcharger la page
// WHERE stock > 0 → on ne montre pas les maillots en rupture de stock
$maillots = $pdo->query('SELECT * FROM maillot WHERE stock > 0 ORDER BY id DESC LIMIT 6')->fetchAll();

include 'includes/header.php';
?>

<!-- Section hero : grand bandeau d'accroche en haut de la page -->
<section class="hero">
    <div class="hero-label">Collection 2024 / 2025</div>
    <h1>Les meilleurs maillots,<br><span>personnalisés pour vous</span></h1>
    <p>Commandez le maillot de votre équipe favorite et personnalisez-le avec votre nom et numéro.</p>
    <div class="hero-btns">
        <a href="catalogue.php" class="btn btn-primary btn-lg">Voir le catalogue</a>
        <!-- Bouton "Créer un compte" visible seulement si l'utilisateur n'est pas connecté -->
        <?php if (!isset($_SESSION['utilisateur_id'])): ?>
            <a href="register.php" class="btn btn-outline btn-lg">Créer un compte</a>
        <?php endif; ?>
    </div>
</section>

<!-- Grille des 6 maillots les plus récents -->
<?php if (!empty($maillots)): ?>
<h2 class="section-title">Nouveautés</h2>
<div class="maillots-grid">
    <?php foreach ($maillots as $m): ?>
        <div class="maillot-card">
            <!-- onerror : si l'image n'existe pas, on affiche une image de remplacement -->
            <img src="<?= htmlspecialchars($m['image_url'] ?? '') ?>"
                 alt="<?= htmlspecialchars($m['nom']) ?>"
                 onerror="this.src='https://placehold.co/400x500/1a1a24/00c875?text=FootStyle'">
            <div class="maillot-card-body">
                <div class="maillot-card-equipe"><?= htmlspecialchars($m['equipe']) ?></div>
                <div class="maillot-card-nom"><?= htmlspecialchars($m['nom']) ?></div>
                <div class="maillot-card-footer">
                    <span class="maillot-card-prix"><?= number_format($m['prix'], 2, ',', ' ') ?> €</span>
                    <!-- Si connecté → page détail du maillot, sinon → page de connexion -->
                    <a href="<?= isset($_SESSION['utilisateur_id']) ? 'maillot.php?id=' . $m['id'] : 'login.php' ?>"
                       class="btn btn-primary btn-sm">Voir</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Section "Points forts" : mise en avant des avantages du site -->
<h2 class="section-title">Pourquoi FootStyle ?</h2>
<div class="stats-grid" style="margin-bottom: 0;">
    <div class="stat-card">
        <div class="stat-label">Personnalisation</div>
        <div class="stat-value" style="font-size:1.4rem; margin-bottom: 8px;">Nom &amp; Numéro</div>
        <div class="stat-unit">Ajoutez vos informations directement sur le maillot</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Catalogue</div>
        <div class="stat-value" style="font-size:1.4rem; margin-bottom: 8px;">+10 équipes</div>
        <div class="stat-unit">Les plus grands clubs européens disponibles</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Livraison</div>
        <div class="stat-value" style="font-size:1.4rem; margin-bottom: 8px;">Rapide &amp; sûre</div>
        <div class="stat-unit">Commande traitée sous 48h</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Compte</div>
        <div class="stat-value" style="font-size:1.4rem; margin-bottom: 8px;">Historique</div>
        <div class="stat-unit">Retrouvez toutes vos commandes passées</div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
