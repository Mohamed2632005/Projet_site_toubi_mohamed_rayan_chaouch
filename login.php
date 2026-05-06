<?php
// ============================================
// Formulaire de connexion (elle fonctione grace au POST)
// ============================================

// On démarre la session
session_start();

// Connexion à la BDD — donne accès à l'objet $pdo pour toutes les requêtes SQL
require 'config/db.php';

// Si l'utilisateur est déjà connecté, alors inutile d'afficher cette page → on redirige direct
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: catalogue.php');
    exit;
}

// Variable d'erreur vide au départ — sera remplie si les identifiants sont incorrects
$erreur = 'identifiants sont incorrects';

// On traite le formulaire seulement quand l'utilisateur clique sur "Se connecter" (méthode POST)
// GET = juste consulter la page, POST = données envoyées via le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // trim() supprime les espaces au début et à la fin (évite les erreurs de frappe comme "  jean@mail.fr ") google
    $email        = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    // Vérification basique : les deux champs doivent être remplis si non marche pas
    if (empty($email) || empty($mot_de_passe)) {
        $erreur = 'tas pas rempli tout les champs.';
    } else {
        // Requête préparée pour chercher l'utilisateur par son email
        // Le "?" est remplacé de façon sécurisée par PDO — protège contre les injections SQL (je savait pas cetteit bien de l'apprendre)
        $stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE email = ?');
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch(); // retourne les données de l'utilisateur ou false si email introuvable

        // password_verify() compare le mot de passe tapé avec le hash bcrypt stocké en BDD
        // C'est la seule façon correcte de vérifier un mot de passe haché — on ne déchiffre pas, on rehashe et compare
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            // Connexion réussie — on stocke les infos clés dans la session PHP
            $_SESSION['utilisateur_id']  = $utilisateur['id'];                              // ID pour les requêtes SQL (panier, commandes...)
            $_SESSION['utilisateur_nom'] = $utilisateur['prenom'] . ' ' . $utilisateur['nom']; // Nom complet pour l'affichage
            $_SESSION['role']            = $utilisateur['role'];                             // 'admin' ou 'utilisateur' — pour les protections

            // Redirection différente selon le rôle : les admins vont sur le dashboard, les clients sur le catalogue
            if ($utilisateur['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: catalogue.php');
            }
            exit;
        } else {
            // Message volontairement vague : on ne dit pas si c'est l'email OU le mot de passe qui est faux
            // C'est une mesure de sécurité — évite qu'un attaquant sache quels emails existent dans la BDD (j'ai vu sa dans une vidéo youtube)
            $erreur = 'Email ou mot de passe incorrect .';
        }
    }
}

// On passe le titre et le chemin relatif au header pour afficher le bon titre d'onglet et charger le bon CSS
$titre = 'Connexion';
$base_path = '';
include 'includes/header.php';
?>

<!-- Carte de connexion centrée sur la page -->
<div class="form-card">
    <div class="form-title">Connexion</div>
    <div class="form-sub">Accédez à votre espace FootStyle</div>

    <!-- Message d'erreur si les identifiants sont incorrects -->
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <!-- Confirmation de succès si on arrive depuis register.php avec ?inscrit=1 dans l'URL -->
    <?php if (isset($_GET['inscrit'])): ?>
        <div class="alert alert-success">Compte créé avec succès ! Connectez-vous.</div>
    <?php endif; ?>

    <!-- Formulaire en POST pour que le mot de passe n'apparaisse pas dans l'URL -->
    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="email">Adresse email</label>
            <!-- value pré-remplit le champ si le formulaire a été soumis avec une erreur — confort utilisateur -->
            <input type="email" id="email" name="email" placeholder="jean@exemple.fr"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="mot_de_passe">Mot de passe</label>
            <!-- type="password" masque les caractères à la saisie -->
            <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
    </form>

    <!-- Lien vers la création de compte pour les nouveaux visiteurs -->
    <div class="form-footer">
        Pas encore de compte ? <a href="register.php">Créer un compte</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
