<?php
// ============================================
// La config nessesaire poure la connexion PDO à la base (utile pour toute les pages qui ont besoin de la BDD)
// ============================================

// Les paramètres de connexion à MySQL
define('DB_HOST', 'localhost');  // adresse du serveur MySQL
define('DB_NAME', 'footstyle'); // nom exact de la base de données créée dans phpMyAdmin
define('DB_USER', 'root');      // nom d'utilisateur MySQL
define('DB_PASS', '');          // mot de passe MySQL (actuellement il est vide par defaut vu que c'est local)

// On tente d'ouvrir la connexion PDO dans un try/catch pour gérer proprement les erreurs
// LePDO permet de faire des requêtes SQL sécurisées grâce aux requêtes préparées
// qui protègent contre les injections SQL (attaque classique si on construit les requêtes en concaténant des strings)
try {
    $pdo = new PDO(
        // Chaîne de connexion : driver mysql, hôte, nom de la BDD, encodage utf8mb4
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            // ERRMODE_EXCEPTION : permet si une requête SQL plante → une exception PHP est levée au lieu de passer silencieusement
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // FETCH_ASSOC : fait que les résultats des requêtes sont retournés sous forme de tableaux associatifs 
            // (en gros array('colonne1' => 'valeur', 'colonne2' => 'valeur')
            //  plutot que tableaux indexés numériquement)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // Si la connexion échoue
    // on arrête tout et on affiche un message d'erreur clair pour aider au débogage
    // on utilise htmlspecialchars() sur le message pour éviter d'afficher du HTML malveillant dans la page d'erreur
    die('<p style="font-family:sans-serif;color:red;padding:20px;">
        Erreur de connexion BDD : ' . htmlspecialchars($e->getMessage()) . '<br>
        Vérifiez config/db.php et que la base "footstyle" existe.
    </p>');
}
