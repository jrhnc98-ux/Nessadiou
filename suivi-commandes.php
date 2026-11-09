<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/routes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['client'])) {
    header('Location: ' . BASE_URL . '/router.php?route=compte/login');
    exit();
}

$dbDisponible = isset($conn) && $conn instanceof mysqli;
$commandes = [];
$parPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalCommandes = 0;
$totalPages = 1;

// ==========================================
// 1. RÉCUPÉRATION DES COMMANDES DE LA BASE DE DONNÉES
// ==========================================
if ($dbDisponible) {
    $clientId = (int) $_SESSION['client']['id'];

    $compte = $conn->prepare('SELECT COUNT(*) AS total FROM commandes WHERE client_id = ?');
    if ($compte) {
        $compte->bind_param('i', $clientId);
        $compte->execute();
        $totalCommandes = (int) $compte->get_result()->fetch_assoc()['total'];
    }
    
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
    if ($stmt) {
        $stmt->bind_param('iii', $clientId, $parPage, $offset);
        $stmt->execute();
        $commandes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// ==========================================
// 2. AJOUT DES COMMANDES LOCALES (SESSION)
// ==========================================
$commandesDemo = $_SESSION['commandes_demo'] ?? [];

if (!empty($commandesDemo)) {
    // On inverse le tableau pour afficher la plus récente en premier
    $commandesDemo = array_reverse($commandesDemo, true);
    
    foreach ($commandesDemo as $cmd) {
        // On formate les données pour qu'elles ressemblent à celles de la BDD
        $commandes[] = [
            'id' => $cmd['id'],
            'statut' => $cmd['statut'],
            'date_commande' => $cmd['date'],
            'total' => $cmd['total'],
            'nombre_produits' => array_sum(array_column($cmd['articles'], 'quantite'))
        ];
    }
}

// Fonction pour afficher la bonne couleur du badge selon le statut
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
<?php if (!$dbDisponible && empty($commandesDemo)) { ?>
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
        <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres" class="orders-empty-button">Découvrir nos produits <span aria-hidden="true">→</span></a>
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
        <a class="order-summary" href="<?php echo BASE_URL; ?>/router.php?route=compte/commande&id=<?php echo (int) $commande['id']; ?>">
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
<?php } ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>