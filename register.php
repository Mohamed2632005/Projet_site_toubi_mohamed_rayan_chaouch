<?php
// ============================================
// Formulaire d'inscription (on peut créer un compte pour pouvoir commander grace a la methode POST)
// ============================================

// On démarre la session — c'est utile si on veut connecter automatiquement l'utilisateur après inscription
session_start();

// Connexion à la BDD — c'est nécessaire pour vérifier si l'email existe déjà et pour insérer le nouveau compte
require 'config/db.php';

// Si l'utilisateur est déjà connecté, il n'a pas besoin de s'inscrire → on le redirige directe
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: catalogue.php');
    exit;
}

// Variables pour les messages de retour affichés à l'utilisateur
$erreur  = 'ya un problème cheff';
$success = 'Tout est bon pour moi cheff';

// On traite le formulaire seulement quand il est soumis avec (méthode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des champs — trim() enlève les espaces inutiles en début et à la fin
    $nom          = trim($_POST['nom'] ?? '');
    $prenom       = trim($_POST['prenom'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirm      = $_POST['confirm'] ?? '';

    // Validation étape 1 : tous les champs obligatoires doivent être remplis
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';

    // Validation étape 2 : les deux champs mot de passe doivent être identiques
    } elseif ($mot_de_passe !== $confirm) {
        $erreur = 'Les mots de passe ne correspondent pas.';

    // Validation étape 3 : on impose un minimum de 6 caractères pour la sécurité
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';

    } else {
        // On vérifie en BDD qu'aucun compte n'existe déjà avec cet email
        // On ne sélectionne que l'id (plus rapide que SELECT *) — on veut juste savoir s'il existe
        $stmt = $pdo->prepare('SELECT id FROM utilisateur WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            // Un compte avec cet email existe déjà alors on ne peut pas créer un doublon
            $erreur = 'Cette adresse email est déjà utilisée.';
        } else {
            // password_hash() chiffre le mot de passe avec l'algorithme bcrypt avant stockage en BDD 
            // (bcrypt c'est un algorithme de hachage)
            // PASSWORD_DEFAULT utilise toujours le meilleur algorithme disponible — on ne stocke JAMAIS en clair
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            // Ici on insertion du nouveau compte en BDD — le rôle par défaut 'utilisateur' est défini dans la table
            $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nom, $prenom, $email, $hash]);

            // Redirection vers la page de connexion avec ?inscrit=1 pour afficher le message de succès
            header('Location: login.php?inscrit=1');
            exit;
        }
    }
}

// Titre de la page et chemin relatif pour les ressources CSS/JS
$titre = 'Inscription';
$base_path = '';
include 'includes/header.php';
?>

<!-- Carte d'inscription centrée sur la page -->
<div class="form-card" style="max-width: 520px;">
    <div class="form-title">Créer un compte</div>
    <div class="form-sub">Rejoignez FootStyle et commandez vos maillots</div>

    <!-- Message d'erreur si le formulaire est invalide (champ vide, email pris, mot de passe trop court...) -->
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <!-- Formulaire de création de compte — les champs sont pré-remplis après une erreur pour ne pas tout retaper -->
    <form method="POST" action="register.php">
        <!-- Prénom et nom sur la même ligne grâce à la classe form-row -->
        <div class="form-row">
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" placeholder="Jean"
                       value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" placeholder="Dupont"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
            </div>
        </div>

        <!-- Champ email — type="email" force le navigateur à vérifier le format avant envoi -->
        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" placeholder="jean@exemple.fr"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <!-- Mot de passe et confirmation côte à côte — la comparaison des deux est faite côté PHP -->
        <div class="form-row">
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe"
                       placeholder="Min. 6 caractères" required>
            </div>
            <div class="form-group">
                <label for="confirm">Confirmer</label>
                <input type="password" id="confirm" name="confirm"
                       placeholder="Répétez le mot de passe" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
    </form>

    <!-- Lien vers la connexion pour les utilisateurs qui ont déjà un compte -->
    <div class="form-footer">
        Déjà inscrit ? <a href="login.php">Se connecter</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
