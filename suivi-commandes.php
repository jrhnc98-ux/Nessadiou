<?php
require_once __DIR__ . '/../../config/connexion.php';
session_start();

if (!isset($_SESSION['client'])) {
    header('Location: ' . route_url('login'));
    exit();
}

$dbDisponible = $conn instanceof mysqli;
$commandes = [];
$parPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalCommandes = 0;
$totalPages = 1;

if ($dbDisponible) {
    $clientId = (int) $_SESSION['client']['id'];

    $compte = $conn->prepare('SELECT COUNT(*) AS total FROM commandes WHERE client_id = ?');
    $compte->bind_param('i', $clientId);
    $compte->execute();
    $totalCommandes = (int) $compte->get_result()->fetch_assoc()['total'];
    $totalPages = max(1, (int) ceil($totalCommandes / $parPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $parPage;

    $stmt = $conn->prepare(
        "SELECT c.id, c.statut, c.date_commande, c.total,
                COUNT(l.id) AS nombre_articles,
                COALESCE(SUM(l.quantite), 0) AS nombre_produits
         FROM commandes c
         LEFT JOIN commande_produit l ON l.commande_id = c.id
         WHERE c.client_id = ?
         GROUP BY c.id, c.statut, c.date_commande, c.total
         ORDER BY c.date_commande DESC, c.id DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('iii', $clientId, $parPage, $offset);
    $stmt->execute();
    $commandes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function badge_commande(string $statut): string
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
<title>Mes commandes</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/commandes.css">
</head>
<body class="page-commandes">
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="page-title">
    <h1>Mes commandes</h1>
    <p>Consultez vos commandes et suivez leur statut.</p>
</section>

<main class="container">
<?php if (!$dbDisponible) { ?>
    <div class="order-alert error">La base de données n'est pas disponible. Vérifie que MySQL est lancé.</div>
<?php } elseif (!$commandes) { ?>
    <div class="aucune-commande orders-empty-state">
        <div class="orders-empty-illustration" aria-hidden="true">
            <svg viewBox="0 0 96 96">
                <rect x="22" y="18" width="52" height="64" rx="8" fill="none" stroke="currentColor" stroke-width="5"/>
                <path d="M37 18v-3c0-4 3-7 7-7h8c4 0 7 3 7 7v3" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                <path d="m35 43 7 7 15-16M35 64h25" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <span class="orders-empty-kicker">Votre historique commence ici</span>
        <h2>Aucune commande pour le moment</h2>
        <p>Parcourez nos collections et choisissez les prochaines plantes qui feront grandir votre jardin.</p>
        <a href="<?php echo route_url('catalogue.arbres'); ?>" class="orders-empty-button">Découvrir nos produits <span aria-hidden="true">→</span></a>
    </div>
<?php } else { ?>
    <div class="orders-summary-list">
        <div class="order-summary-head" aria-hidden="true">
            <span>Commande et date</span>
            <span>Articles</span>
            <span>Statut</span>
            <span>Total</span>
            <span>Détails</span>
        </div>
    <?php foreach ($commandes as $commande) { ?>
        <a class="order-summary" href="<?php echo commande_url((int) $commande['id']); ?>">
            <div class="order-summary-main">
                <strong>Commande n°<?php echo (int) $commande['id']; ?></strong>
                <time><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></time>
            </div>
            <div class="order-summary-count">
                <?php echo (int) $commande['nombre_produits']; ?> article<?php echo (int) $commande['nombre_produits'] > 1 ? 's' : ''; ?>
            </div>
            <span class="badge <?php echo badge_commande($commande['statut']); ?>">
                <?php echo htmlspecialchars($commande['statut'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <strong class="order-summary-total"><?php echo number_format((float) $commande['total'], 0, ',', ' '); ?> XPF</strong>
            <span class="order-summary-arrow" aria-hidden="true">›</span>
        </a>
    <?php } ?>
    </div>

    <?php if ($totalPages > 1) { ?>
    <nav class="pagination" aria-label="Pagination des commandes">
        <div class="pagination-pages">
        <?php if ($page > 1) { ?>
            <a href="?page=<?php echo $page - 1; ?>">Précédent</a>
        <?php } ?>

        <?php
        $debut = max(1, $page - 2);
        $fin = min($totalPages, $page + 2);

        if ($debut > 1) {
        ?>
            <a href="?page=1">1</a>
            <?php if ($debut > 2) { ?>
                <span class="pagination-ellipsis" aria-hidden="true">…</span>
            <?php } ?>
        <?php
        }

        for ($numero = $debut; $numero <= $fin; $numero++) {
        ?>
            <a href="?page=<?php echo $numero; ?>" class="<?php echo $numero === $page ? 'active' : ''; ?>" <?php echo $numero === $page ? 'aria-current="page"' : ''; ?>>
                <?php echo $numero; ?>
            </a>
        <?php
        }

        if ($fin < $totalPages) {
            if ($fin < $totalPages - 1) {
        ?>
                <span class="pagination-ellipsis" aria-hidden="true">…</span>
            <?php } ?>
            <a href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
        <?php } ?>

        <?php if ($page < $totalPages) { ?>
            <a href="?page=<?php echo $page + 1; ?>">Suivant</a>
        <?php } ?>
        </div>

        <form method="GET" class="page-jump">
            <label for="page-cible">Aller à la page</label>
            <input
                type="number"
                id="page-cible"
                name="page"
                min="1"
                max="<?php echo $totalPages; ?>"
                value="<?php echo $page; ?>"
                required
            >
            <button type="submit">OK</button>
        </form>
    </nav>
    <?php } ?>
<?php } ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
