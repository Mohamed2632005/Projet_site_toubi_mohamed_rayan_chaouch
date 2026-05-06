<?php
// ============================================
//  Simple logout : Déconnexion pour l'utilisateur (détruit la session)
// ============================================

// session_start() est obligatoire même ici — sans ça, PHP ne sait pas quelle session manipuler
session_start();

// session_unset() vide toutes les variables de session : $_SESSION['utilisateur_id'], $_SESSION['role'], etc.
// Après ça, $_SESSION est un tableau vide — l'utilisateur n'est plus reconnu dans les vérifications
session_unset();

// session_destroy() supprime définitivement le fichier de session côté serveur
// C'est le double coup de sécurité : unset vide les données, destroy ferme la session
session_destroy();

// Redirection vers la page de connexion — l'utilisateur doit se reconnecter pour accéder au site
header('Location: login.php');
exit;
