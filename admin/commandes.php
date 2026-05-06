<?php
// ============================================
// Ici c'est le Gestionaire des commandes en gros pour l'admin,
// on peut voir toutes les commandes, les détails et changer leur statut
// ============================================

// On lance la session pour pouvoir vérifier les droits de l'utilisateur connecté
session_start();
require '../config/db.php';

// Protection double : l'utilisateur doit être connecté ET avoir le rôle 'admin'
// Si une condition échoue, on redirige vers la connexion
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Message de succès affiché après mise à jour d'un statut — vide au départ
$success = '';

// Liste des statuts autorisés — sert à valider le statut reçu en POST
// Évite qu'un admin injecte une valeur arbitraire dans la BDD
$statuts_valides = ['en cours', 'expédié', 'livré', 'annulé'];

// Traitement du formulaire de changement de statut — déclenché quand on clique sur "Mettre à jour"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_commande'])) {
    $id_commande = intval($_POST['id_commande']); // intval() sécurise l'ID contre les valeurs non numériques
    $statut      = $_POST['statut'] ?? '';

    // in_array() vérifie que le statut envoyé fait bien partie des valeurs autorisées
    // Si quelqu'un tente d'envoyer un statut inventé, on ne met pas à jour la BDD
    if (in_array($statut, $statuts_valides)) {
        $stmt = $pdo->prepare('UPDATE commande SET statut = ? WHERE id = ?');
        $stmt->execute([$statut, $id_commande]);
        $success = 'Statut de la commande #' . $id_commande . ' mis à jour.';
    }
}

// Récupération de toutes les commandes avec les infos du client grâce à un JOIN
// On joint la table utilisateur pour avoir le nom, prénom et email sans requête supplémentaire
// ORDER BY date_commande DESC → les plus récentes en premier
$commandes = $pdo->query('
    SELECT c.*, u.nom, u.prenom, u.email
    FROM commande c
    JOIN utilisateur u ON c.id_utilisateur = u.id
    ORDER BY c.date_commande DESC
')->fetchAll();

// Pour chaque commande, on récupère ses lignes (les articles commandés avec personnalisation)
// On stocke dans un tableau indexé par ID de commande : $lignes_par_commande[5] = [ligne1, ligne2, ...]
$lignes_par_commande = [];
foreach ($commandes as $cmd) {
    $stmt = $pdo->prepare('
        SELECT cl.*, m.nom AS maillot_nom, m.equipe
        FROM commande_ligne cl
        JOIN maillot m ON cl.id_maillot = m.id
        WHERE cl.id_commande = ?
    ');
    $stmt->execute([$cmd['id']]);
    $lignes_par_commande[$cmd['id']] = $stmt->fetchAll();
}

// Titre de la page et chemin relatif pour les ressources (on est dans /admin)
$titre = 'Gestion des commandes';
$base_path = '../';
include '../includes/header.php';
?>

<!-- Titre de la page et navigation admin -->
<h1 class="page-title">Gestion des commandes</h1>

<!-- Navigation admin -->
<div class="admin-nav">
    <a href="dashboard.php"  class="btn btn-outline">Dashboard</a>
    <a href="maillots.php"   class="btn btn-outline">Maillots</a>
    <a href="commandes.php"  class="btn btn-primary">Commandes</a>
</div>

<!-- Message de succès affiché après une mise à jour de statut -->
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Si aucune commande : état vide, sinon on boucle et on affiche tout -->
<?php if (empty($commandes)): ?>
    <div class="empty-state">
        <div class="empty-icon">&#128230;</div>
        <p>Aucune commande pour l'instant.</p>
    </div>
<?php else: ?>

    <!-- Nombre total de commandes -->
    <p class="page-subtitle"><?= count($commandes) ?> commande<?= count($commandes) > 1 ? 's' : '' ?> au total</p>

    <?php foreach ($commandes as $cmd):
        // Sélection de la classe CSS du badge selon le statut actuel de la commande
        $badge_class = match($cmd['statut']) {
            'livré'    => 'badge-green',
            'expédié'  => 'badge-yellow',
            'annulé'   => 'badge-red',
            default    => 'badge-gray', // couvre 'en cours' et toute autre valeur
        };
    ?>
    <!-- Carte d'une commande : en-tête + tableau des articles + formulaire de changement de statut -->
    <div class="commande-item" style="margin-bottom: 16px;">

        <!-- En-tête : numéro de commande, date, nom du client, email, statut, total -->
        <div class="commande-item-header" style="margin-bottom: 16px;">
            <div>
                <span class="commande-id">Commande #<?= $cmd['id'] ?></span>
                <div class="commande-date">
                    <!-- date() reformate la date SQL en format lisible : "12/03/2025 à 14:30" -->
                    <?= date('d/m/Y à H:i', strtotime($cmd['date_commande'])) ?>
                    &mdash; <?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?>
                    (<span style="font-size:.78rem;"><?= htmlspecialchars($cmd['email']) ?></span>)
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cmd['statut']) ?></span>
                <span class="commande-total"><?= number_format($cmd['montant_total'], 2, ',', ' ') ?> €</span>
            </div>
        </div>

        <!-- Tableau des articles de cette commande (maillots + personnalisation + quantités) -->
        <?php if (!empty($lignes_par_commande[$cmd['id']])): ?>
            <div class="table-card" style="margin-bottom: 14px;">
                <table>
                    <thead>
                        <tr>
                            <th>Équipe</th>
                            <th>Maillot</th>
                            <th>Personnalisation</th>
                            <th>Qté</th>
                            <th>Prix unit.</th>
                            <th>Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes_par_commande[$cmd['id']] as $ligne): ?>
                        <tr>
                            <td><?= htmlspecialchars($ligne['equipe']) ?></td>
                            <td><?= htmlspecialchars($ligne['maillot_nom']) ?></td>
                            <td style="font-size:.82rem; color:var(--text-muted);">
                                <!-- Affichage de la personnalisation ou "—" si aucune -->
                                <?= $ligne['personnalisation_nom'] ? htmlspecialchars($ligne['personnalisation_nom']) : '' ?>
                                <?= $ligne['personnalisation_numero'] ? ' #' . $ligne['personnalisation_numero'] : '' ?>
                                <?= (!$ligne['personnalisation_nom'] && !$ligne['personnalisation_numero']) ? '—' : '' ?>
                            </td>
                            <td><?= $ligne['quantite'] ?></td>
                            <td><?= number_format($ligne['prix_unitaire'], 2, ',', ' ') ?> €</td>
                            <!-- Sous-total de la ligne = prix unitaire × quantité -->
                            <td><strong><?= number_format($ligne['prix_unitaire'] * $ligne['quantite'], 2, ',', ' ') ?> €</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Formulaire pour changer le statut de cette commande -->
        <!-- Chaque commande a son propre formulaire avec son id_commande en champ caché -->
        <form method="POST" action="commandes.php" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <!-- Champ caché pour identifier quelle commande on modifie -->
            <input type="hidden" name="id_commande" value="<?= $cmd['id'] ?>">
            <label style="font-size:.85rem; color:var(--text-muted); font-weight:600;">Changer le statut :</label>
            <!-- Menu déroulant pré-sélectionné sur le statut actuel de la commande -->
            <select name="statut" style="background:var(--surface-2); border:1px solid var(--border);
                border-radius:6px; color:var(--text); padding:6px 12px;
                font-family:var(--font); font-size:.85rem; outline:none;">
                <?php foreach ($statuts_valides as $s): ?>
                    <!-- selected sur le statut actuel pour que le bon soit pré-coché -->
                    <option value="<?= $s ?>" <?= $cmd['statut'] === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?> <!-- ucfirst() met la première lettre en majuscule -->
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Mettre à jour</button>
        </form>

    </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>
