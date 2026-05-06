<?php
// ============================================
// Le gestionnaire des maillots
// ============================================

session_start(); // Démarrage de la session pour accéder à $_SESSION['utilisateur_id'] et $_SESSION['role']
require '../config/db.php';

// Protection double : connexion + rôle admin obligatoires
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$erreur  = 'non tas pas le droit t es pas admin';
$success = 'bienvenu mr le bossadmin';

// En gros suppression d'un maillot via ?supprimer=ID dans l'URL
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);

    // Avant de supprimer, on vérifie qu'aucune commande passée ne contient ce maillot
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM commande_ligne WHERE id_maillot = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $erreur = 'Impossible de supprimer ce maillot : il est lié à des commandes existantes.';
    } else {
        // On supprime aussi les entrées dans le panier (pour éviter des articles orphelins)
        $pdo->prepare('DELETE FROM panier WHERE id_maillot = ?')->execute([$id]);
        // Puis on supprime le maillot lui-même
        $pdo->prepare('DELETE FROM maillot WHERE id = ?')->execute([$id]);
        $success = 'Maillot supprimé avec succès.';
    }
}

// Ajout d'un nouveau maillot via le formulaire en bas de page action="ajouter"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom         = trim($_POST['nom'] ?? '');
    $equipe      = trim($_POST['equipe'] ?? '');
    // str_replace gère le cas où l'utilisateur entre "89,99" au lieu de "89.99" (sa remplace point par vergule pour que le floatval puisse convertir correctement)
    $prix        = floatval(str_replace(',', '.', $_POST['prix'] ?? '0'));
    $stock       = intval($_POST['stock'] ?? 0);
    $image_url   = trim($_POST['image_url'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation minimale : nom, équipe et prix sont obligatoires
    if (empty($nom) || empty($equipe) || $prix <= 0) {
        $erreur = 'Le nom, l\'équipe et le prix sont obligatoires.';
    } else {
        // Insertion du nouveau maillot en BDD
        $stmt = $pdo->prepare('
            INSERT INTO maillot (nom, equipe, prix, stock, image_url, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$nom, $equipe, $prix, $stock, $image_url, $description]);
        $success = 'Maillot "' . htmlspecialchars($nom) . '" ajouté avec succès.';
    }
}

// Mise à jour du stock d'un maillot existant via les formulaires inline du tableau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'stock') {
    $id    = intval($_POST['id_maillot']);
    $stock = intval($_POST['stock']);
    $stmt  = $pdo->prepare('UPDATE maillot SET stock = ? WHERE id = ?');
    $stmt->execute([$stock, $id]);
    $success = 'Stock mis à jour.';
}

// Récupération de tous les maillots pour le tableau — triés par équipe puis par nom
$maillots = $pdo->query('SELECT * FROM maillot ORDER BY equipe, nom')->fetchAll();

$titre = 'Gestion des maillots';
$base_path = '../';
include '../includes/header.php';
?>

<h1 class="page-title">Gestion des maillots</h1>

<!-- Navigation entre les pages admin -->
<div class="admin-nav">
    <a href="dashboard.php"  class="btn btn-outline">Dashboard</a>
    <a href="maillots.php"   class="btn btn-primary">Maillots</a>
    <a href="commandes.php"  class="btn btn-outline">Commandes</a>
</div>

<!-- Messages d'erreur ou de succès selon l'action effectuée -->
<?php if ($erreur): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Formulaire d'ajout d'un nouveau maillot au catalogue -->
<div class="table-card" style="margin-bottom: 28px; padding: 24px; border-radius: var(--radius);">
    <h2 style="font-size:1.1rem; font-weight:700; margin-bottom: 20px;">Ajouter un maillot</h2>
    <!-- action="ajouter" distingue ce formulaire du formulaire de mise à jour de stock -->
    <form method="POST" action="maillots.php">
        <input type="hidden" name="action" value="ajouter">
        <div class="form-row">
            <div class="form-group">
                <label>Nom du maillot *</label>
                <input type="text" name="nom" placeholder="Ex : Maillot Domicile 2024/25" required>
            </div>
            <div class="form-group">
                <label>Équipe *</label>
                <input type="text" name="equipe" placeholder="Ex : PSG" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Prix (€) *</label>
                <!-- step="0.01" permet d'entrer des prix avec centimes -->
                <input type="number" name="prix" placeholder="89.99" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Stock initial</label>
                <input type="number" name="stock" placeholder="0" min="0" value="0">
            </div>
        </div>
        <div class="form-group">
            <label>URL de l'image</label>
            <input type="text" name="image_url" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Description du maillot..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Ajouter le maillot</button>
    </form>
</div>

<!-- Tableau de tous les maillots du catalogue avec gestion du stock et suppression -->
<h2 class="section-title">Catalogue (<?= count($maillots) ?> maillots)</h2>

<?php if (empty($maillots)): ?>
    <div class="empty-state"><p>Aucun maillot dans le catalogue.</p></div>
<?php else: ?>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Équipe</th>
                    <th>Nom</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Modifier stock</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($maillots as $m): ?>
                <tr>
                    <td><strong>#<?= $m['id'] ?></strong></td>
                    <td><?= htmlspecialchars($m['equipe']) ?></td>
                    <td><?= htmlspecialchars($m['nom']) ?></td>
                    <td><?= number_format($m['prix'], 2, ',', ' ') ?> €</td>
                    <td>
                        <!-- Couleur du badge selon le niveau de stock : rouge=0, jaune<10, vert>=10 -->
                        <?php if ($m['stock'] == 0): ?>
                            <span class="badge badge-red">Rupture</span>
                        <?php elseif ($m['stock'] < 10): ?>
                            <span class="badge badge-yellow"><?= $m['stock'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-green"><?= $m['stock'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Formulaire inline par maillot pour modifier le stock directement dans le tableau -->
                        <!-- action="stock" distingue ce formulaire du formulaire d'ajout -->
                        <form method="POST" action="maillots.php" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="action"     value="stock">
                            <input type="hidden" name="id_maillot" value="<?= $m['id'] ?>">
                            <input type="number" name="stock" value="<?= $m['stock'] ?>"
                                   min="0" style="width:70px; padding:4px 8px; font-size:.85rem;
                                   background:var(--surface-2); border:1px solid var(--border);
                                   border-radius:6px; color:var(--text); font-family:var(--font);">
                            <button type="submit" class="btn btn-ghost btn-sm">OK</button>
                        </form>
                    </td>
                    <td>
                        <!-- Bouton de suppression avec confirmation JavaScript pour éviter les suppressions accidentelles -->
                        <a href="maillots.php?supprimer=<?= $m['id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Supprimer ce maillot définitivement ?')">
                            Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
