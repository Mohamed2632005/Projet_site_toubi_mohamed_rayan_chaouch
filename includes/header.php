<?php
// ============================================
// Inclus dans toutes les pages — génère le <head>, la nav et ouvre le <main>
// ============================================

// Si la page appelante n'a pas défini $titre, on met 'FootStyle' par défaut (opérateur ??)
// $base_path = '' pour les pages à la racine, '../' pour les pages dans /admin
// Ça permet d'utiliser les mêmes liens CSS et nav depuis n'importe quel dossier
$titre      = $titre      ?? 'FootStyle';
$base_path  = $base_path  ?? '';

// On compte le total d'articles dans le panier pour afficher le badge numérique dans la nav
// COALESCE(SUM(...), 0) retourne 0 si le panier est vide au lieu de retourner NULL
$panier_count = 0;
if (isset($_SESSION['utilisateur_id']) && isset($pdo)) {
    $s = $pdo->prepare('SELECT COALESCE(SUM(quantite), 0) FROM panier WHERE id_utilisateur = ?');
    $s->execute([$_SESSION['utilisateur_id']]);
    $panier_count = (int) $s->fetchColumn(); // (int) force le résultat en entier
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!-- viewport : indispensable pour que le site soit correctement affiché sur mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Le titre de l'onglet est dynamique : "Catalogue — FootStyle", "Connexion — FootStyle", etc. -->
    <title><?= htmlspecialchars($titre) ?> — FootStyle</title>
    <!-- Préconnexion à Google Fonts pour accélérer le chargement de la police -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Chargement de la police Poppins en plusieurs graisses (400=normal, 600=semi-gras, 800=extra-gras) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Feuille de style principale — le chemin est relatif grâce à $base_path (ex: '../css/style.css' depuis /admin) -->
    <link rel="stylesheet" href="<?= $base_path ?>css/style.css">
</head>
<body>

<!-- Barre de navigation principale, présente sur toutes les pages du site -->
<nav class="nav">
    <div class="container nav-inner">
        <!-- Logo cliquable qui ramène toujours à la page d'accueil -->
        <a href="<?= $base_path ?>index.php" class="nav-logo">Foot<span>Style</span></a>

        <ul class="nav-links">
            <!-- Liens toujours visibles, peu importe la connexion -->
            <li><a href="<?= $base_path ?>index.php">Accueil</a></li>
            <li><a href="<?= $base_path ?>catalogue.php">Catalogue</a></li>

            <!-- Les liens suivants changent selon si l'utilisateur est connecté ou non -->
            <?php if (isset($_SESSION['utilisateur_id'])): ?>
                <li>
                    <a href="<?= $base_path ?>panier.php" class="nav-panier">
                        Panier
                        <!-- Badge avec le nombre d'articles — affiché seulement si le panier n'est pas vide -->
                        <?php if ($panier_count > 0): ?>
                            <span class="panier-badge"><?= $panier_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="<?= $base_path ?>mon_compte.php">Mon compte</a></li>
                <!-- Le lien vers le panel admin n'est visible que pour les utilisateurs avec le rôle 'admin' -->
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?= $base_path ?>admin/dashboard.php" class="btn btn-sm btn-outline">Admin</a></li>
                <?php endif; ?>
                <li><a href="<?= $base_path ?>logout.php" class="btn btn-sm btn-danger">Déconnexion</a></li>
            <?php else: ?>
                <!-- Utilisateur non connecté : on l'invite à s'inscrire ou se connecter -->
                <li><a href="<?= $base_path ?>register.php">Inscription</a></li>
                <li><a href="<?= $base_path ?>login.php" class="btn btn-sm btn-primary">Connexion</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Ouverture du contenu principal de la page — le </main> et </div> sont dans footer.php -->
<main class="main">
    <div class="container">
