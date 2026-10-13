<?php
require_once __DIR__ . '/../../config/connexion.php';
session_start();

if (!isset($_SESSION['client']) || ($_SESSION['client']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("<p class='access-denied'>Accès refusé — réservé aux administrateurs.</p>");
}

$dbDisponible = $conn instanceof mysqli;
$commandes = [];
$statutsAutorises = ['En cours', 'En préparation', 'En livraison', 'Livré', 'Annulée'];

if ($dbDisponible && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $commandeId = (int) ($_POST['commande_id'] ?? 0);
    $nouveauStatut = $_POST['statut'] ?? '';

    if ($commandeId > 0 && in_array($nouveauStatut, $statutsAutorises, true)) {
        $stmt = $conn->prepare('UPDATE commandes SET statut = ? WHERE id = ?');
        $stmt->bind_param('si', $nouveauStatut, $commandeId);
        $stmt->execute();
    }

    header('Location: ' . route_url('admin.commandes'));
    exit();
}

if ($dbDisponible) {
    $result = $conn->query(
        "SELECT c.id, c.statut, c.date_commande, c.total,
                cl.prenom, cl.nom, cl.email,
                l.nom_produit, l.quantite, l.prix_unitaire
         FROM commandes c
         JOIN clients cl ON cl.id = c.client_id
         LEFT JOIN commande_produit l ON l.commande_id = c.id
         ORDER BY c.date_commande DESC, c.id DESC, l.id ASC"
    );

    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        $id = (int) $row['id'];
        if (!isset($commandes[$id])) {
            $commandes[$id] = $row;
            $commandes[$id]['articles'] = [];
        }
        if ($row['nom_produit'] !== null) {
            $commandes[$id]['articles'][] = $row;
        }
    }
}

function badge_admin(string $statut): string
{
    return match ($statut) {
        'En préparation' => 'badge-preparation',
        'En livraison' => 'badge-livraison',
        'Livré' => 'badge-livre',
        'Annulée' => 'badge-annulee',
        default => 'badge-encours',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Administration — Commandes</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/commandes.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
</head>
<body class="page-commandes">
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="page-title">
    <h1>Administration — Commandes</h1>
    <p>Consultez les articles commandés et mettez à jour leur statut.</p>
</section>

<main class="container">
<?php if (!$dbDisponible) { ?>
    <div class="order-alert error">La base de données n'est pas disponible. Vérifie que MySQL est lancé.</div>
<?php } elseif (!$commandes) { ?>
    <p class="empty-note">Aucune commande enregistrée pour le moment.</p>
<?php } else { ?>
    <div class="orders-list">
    <?php foreach ($commandes as $commande) { ?>
        <article class="order-card admin-order-card">
            <header class="order-card-header">
                <div>
                    <h2>Commande n°<?php echo (int) $commande['id']; ?></h2>
                    <p><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <small><?php echo htmlspecialchars($commande['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <time><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></time>
                </div>
                <span class="badge <?php echo badge_admin($commande['statut']); ?>">
                    <?php echo htmlspecialchars($commande['statut'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </header>

            <ul class="admin-order-items">
            <?php foreach ($commande['articles'] as $article) { ?>
                <li>
                    <span><?php echo htmlspecialchars($article['nom_produit'], ENT_QUOTES, 'UTF-8'); ?> × <?php echo (int) $article['quantite']; ?></span>
                    <strong><?php echo number_format((float) $article['prix_unitaire'] * (int) $article['quantite'], 0, ',', ' '); ?> XPF</strong>
                </li>
            <?php } ?>
            </ul>

            <footer class="admin-order-footer">
                <strong>Total : <?php echo number_format((float) $commande['total'], 0, ',', ' '); ?> XPF</strong>
                <form method="POST" class="status-form">
                    <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                    <select name="statut" class="statut-select">
                    <?php foreach ($statutsAutorises as $statut) { ?>
                        <option value="<?php echo htmlspecialchars($statut, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $commande['statut'] === $statut ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($statut, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php } ?>
                    </select>
                    <button type="submit" class="btn-update">Mettre à jour</button>
                </form>
            </footer>
        </article>
    <?php } ?>
    </div>
<?php } ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
