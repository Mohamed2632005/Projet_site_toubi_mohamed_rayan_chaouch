<?php
// ============================================
// Ici c'est la fiche maillot + personnalisation (on y voit nos maillots en détail,
// on peut choisir la personnalisation et la quantité, et l'ajouter au panier)
// ============================================

session_start(); // Démarrage de la session pour accéder à $_SESSION['utilisateur_id']
require 'config/db.php';

// Protection : connexion requise pour accéder à la fiche d'un maillot
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération et sécurisation de l'ID du maillot passé dans l'URL
// intval() convertit en entier — si l'URL contient "id=abc" on obtient 0 donc redirection dircte
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: catalogue.php');
    exit;
}

// On cherche le maillot dans la BDD avec son ID propre al lui 
$stmt = $pdo->prepare('SELECT * FROM maillot WHERE id = ?');
$stmt->execute([$id]);
$maillot = $stmt->fetch();

// Si aucun maillot trouvé pour cet ID qu'il à ete supprimé ou que son ID est inexistant) alors on fait retour au catalogue
if (!$maillot) {
    header('Location: catalogue.php');
    exit;
}

// Variables pour les messages de retour affichés à l'utilisateur
$erreur  = 'Oula ! Une erreur s est produite cheff';
$success = 'Ok ! Tout est bon pour moi cheff';

// Traitement du formulaire d'ajout au panier, déclenché quand on clique sur "Ajouter au panier"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantite     = intval($_POST['quantite'] ?? 1); // intval() évite d'insérer du texte en guise de quantité
    $perso_nom    = trim($_POST['personnalisation_nom'] ?? '');
    // Si le numéro est vide (champ non rempli), on stocke NULL en BDD plutôt que 0
    $perso_numero = $_POST['personnalisation_numero'] !== '' ? intval($_POST['personnalisation_numero']) : null;

    // Quantité minimum forcée à 1 si l'utilisateur tente de passer une valeur négative ou nulle
    if ($quantite < 1) {
        $quantite = 1;
    }

    // Petite vérif du stock disponible avant d'insérer dans le panier
    if ($quantite > $maillot['stock']) {
        $erreur = 'Stock insuffisant. Seulement ' . $maillot['stock'] . ' disponible(s).';
    } else {
        // Insertion dans le panier — on stocke aussi la personnalisation et la quantité
        $stmt = $pdo->prepare('
            INSERT INTO panier (id_utilisateur, id_maillot, personnalisation_nom, personnalisation_numero, quantite)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $_SESSION['utilisateur_id'],
            $id,
            $perso_nom  ?: null, // ?: null → si la string est vide, on stocke NULL en BDD
            $perso_numero,
            $quantite,
        ]);
        $success = 'Maillot ajouté au panier avec succès !';
    }
}

// Pour avoir titre de l'onglet = nom du maillot + équipe (ex: "Maillot Domicile — PSG")
$titre = htmlspecialchars($maillot['nom']) . ' — ' . htmlspecialchars($maillot['equipe']);
$base_path = '';
include 'includes/header.php';
?>

<!-- Lien de retour vers le catalogue -->
<div style="margin-bottom: 20px;">
    <a href="catalogue.php" class="text-muted" style="font-size:.85rem;">&larr; Retour au catalogue</a>
</div>

<!-- Messages d'erreur ou de succès après soumission du formulaire -->
<?php if ($erreur): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
        <!-- Lien direct vers le panier après ajout réussi -->
        &nbsp;<a href="panier.php" class="btn btn-primary btn-sm">Voir le panier</a>
    </div>
<?php endif; ?>

<!-- Mise en page détail : image à gauche, infos + formulaire à droite -->
<div class="maillot-detail">
    <!-- Image du maillot -->
    <div class="maillot-img-wrap">
        <img src="<?= htmlspecialchars($maillot['image_url'] ?? '') ?>"
             alt="<?= htmlspecialchars($maillot['nom']) ?>"
             onerror="this.src='https://placehold.co/400x500/1a1a24/00c875?text=FootStyle'">
    </div>

    <!-- Infos du maillot + formulaire d'ajout au panier -->
    <div class="maillot-info">
        <div class="maillot-info-equipe"><?= htmlspecialchars($maillot['equipe']) ?></div>
        <h1><?= htmlspecialchars($maillot['nom']) ?></h1>
        <div class="maillot-info-prix"><?= number_format($maillot['prix'], 2, ',', ' ') ?> €</div>

        <!-- Description affichée seulement si elle existe en BDD -->
        <?php if ($maillot['description']): ?>
            <p class="maillot-info-desc"><?= htmlspecialchars($maillot['description']) ?></p>
        <?php endif; ?>

        <!-- Affichage du stock restant — aide l'utilisateur à savoir combien il peut commander -->
        <p class="maillot-stock">
            Stock disponible : <strong class="text-accent"><?= $maillot['stock'] ?></strong> article(s)
        </p>

        <!-- Formulaire de personnalisation et d'ajout au panier -->
        <!-- action renvoie vers cette même page avec l'id pour retraiter le POST -->
        <form method="POST" action="maillot.php?id=<?= $id ?>">

            <!-- Section personnalisation : champs optionnels pour le nom et le numéro sur le maillot -->
            <div class="perso-section">
                <div class="perso-title">Personnalisation (optionnel)</div>
                <div class="form-group">
                    <label for="personnalisation_nom">Nom sur le maillot</label>
                    <!-- maxlength="100" limite la taille côté front, la BDD impose la même limite -->
                    <input type="text" id="personnalisation_nom" name="personnalisation_nom"
                           placeholder="Ex : DUPONT" maxlength="100"
                           value="<?= htmlspecialchars($_POST['personnalisation_nom'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="personnalisation_numero">Numéro (1 – 99)</label>
                    <!-- min/max sont vérifiés côté navigateur — la validation définitive est côté PHP -->
                    <input type="number" id="personnalisation_numero" name="personnalisation_numero"
                           placeholder="Ex : 10" min="1" max="99"
                           value="<?= htmlspecialchars($_POST['personnalisation_numero'] ?? '') ?>">
                </div>
            </div>

            <!-- Quantité : max limité au stock disponible pour éviter les dépassements -->
            <div class="form-group">
                <label for="quantite">Quantité</label>
                <input type="number" id="quantite" name="quantite"
                       value="1" min="1" max="<?= $maillot['stock'] ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Ajouter au panier
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
