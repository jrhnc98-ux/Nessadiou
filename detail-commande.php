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
$commande = null;
$articles = [];
$commandeId = (int) ($_GET['commande_id'] ?? $_GET['id'] ?? 0);
$message = '';
$messageType = '';

// GESTION DE L'ANNULATION
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'annuler_commande') {
    $raison = trim($_POST['raison'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    
    $clientStmt = $dbDisponible ? $conn->prepare('SELECT mot_de_passe FROM clients WHERE id = ? LIMIT 1') : null;
    $mdpValide = false;
    
    if ($clientStmt) {
        $clientId = (int) $_SESSION['client']['id'];
        $clientStmt->bind_param('i', $clientId);
        $clientStmt->execute();
        $hash = (string) ($clientStmt->get_result()->fetch_assoc()['mot_de_passe'] ?? '');
        $mdpValide = password_verify($motDePasse, $hash) || ($hash !== '' && hash_equals($hash, md5($motDePasse)));
    } else {
        $mdpValide = true; 
    }

    if ($raison === '') {
        $message = "Veuillez indiquer une raison pour l'annulation.";
        $messageType = 'error';
    } elseif (!$mdpValide) {
        $message = "Le mot de passe est incorrect.";
        $messageType = 'error';
    } else {
        if (isset($_SESSION['commandes_demo'][$commandeId])) {
            $_SESSION['commandes_demo'][$commandeId]['statut'] = 'Annulée';
            $_SESSION['commandes_demo'][$commandeId]['raison_annulation'] = $raison;
            $message = "La commande a été annulée avec succès.";
            $messageType = 'success';
        }
    }
}

// RECHERCHE DANS LA SESSION
if (isset($_SESSION['commandes_demo'][$commandeId])) {
    $cmdDemo = $_SESSION['commandes_demo'][$commandeId];
    
    $commande = [
        'id' => $cmdDemo['id'],
        'statut' => $cmdDemo['statut'],
        'date_commande' => $cmdDemo['date'],
        'total' => $cmdDemo['total'],
        'raison_annulation' => $cmdDemo['raison_annulation'] ?? null,
        'date_annulation' => $cmdDemo['date_annulation'] ?? null
    ];

    foreach ($cmdDemo['articles'] as $item) {
        $articles[] = [
            'nom_produit' => $item['nom'],
            'prix_unitaire' => $item['prix'],
            'quantite' => $item['quantite'],
            'image' => $item['image'] ?? null
        ];
    }
}

if (!$commande) {
    http_response_code(404);
}

function badge_detail(string $statut): string
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
<title>Détail de la commande</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/commandes.css">
</head>
<body class="page-commandes">
<?php require __DIR__ . '/../partials/header.php'; ?>

<main class="container order-detail-page">
    <a class="back-orders" href="<?php echo BASE_URL; ?>/router.php?route=compte/commandes">← Retour à mes commandes</a>

    <?php if (!$commande) { ?>
        <section class="order-not-found">
            <div class="order-not-found-code" aria-hidden="true">404</div>
            <h1>Commande introuvable</h1>
            <p>La commande demandée n’existe pas ou n’est pas accessible.</p>
            <a class="btn" href="<?php echo BASE_URL; ?>/router.php?route=compte/commandes">Retour à mes commandes</a>
        </section>
    <?php } else { ?>
        
        <?php if ($message !== '') { ?>
            <div class="order-alert <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <article class="order-card">
            <header class="order-card-header">
                <div>
                    <h1>Commande n°<?php echo (int) $commande['id']; ?></h1>
                    <time><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></time>
                </div>
                <span class="badge <?php echo badge_detail($commande['statut']); ?>">
                    <?php echo htmlspecialchars($commande['statut'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </header>

            <div class="order-items">
            <?php foreach ($articles as $article) {
                $image = $article['image'] ?? null;
                if ($image && strpos($image, 'http') !== 0 && strpos($image, '/') !== 0) {
                    $image = BASE_URL . '/assets/images/' . basename($image);
                }
            ?>
                <div class="order-item">
                    <?php if ($image) { ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <?php } ?>
                    <div class="order-item-name">
                        <strong><?php echo htmlspecialchars($article['nom_produit'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo number_format((float) $article['prix_unitaire'], 0, ',', ' '); ?> XPF × <?php echo (int) $article['quantite']; ?></span>
                    </div>
                    <strong><?php echo number_format((float) $article['prix_unitaire'] * (int) $article['quantite'], 0, ',', ' '); ?> XPF</strong>
                </div>
            <?php } ?>
            </div>

            <footer class="order-card-total">
                Total : <strong><?php echo number_format((float) $commande['total'], 0, ',', ' '); ?> XPF</strong>
            </footer>
        </article>

        <?php if ($commande['statut'] === 'Annulée') { ?>
            <section class="cancellation-summary">
                <h2>Commande annulée</h2>
                <p><strong>Raison :</strong> <?php echo nl2br(htmlspecialchars($commande['raison_annulation'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
            </section>
        <?php } elseif (in_array($commande['statut'], ['En cours', 'En préparation'], true)) { ?>
            <details class="cancel-order">
                <summary>Annuler cette commande</summary>
                <form method="POST" class="cancel-order-form" onsubmit="return confirm('Confirmez-vous définitivement l’annulation de cette commande ?');">
                    <input type="hidden" name="action" value="annuler_commande">
                    
                    <label for="raison">Raison de l’annulation</label>
                    <textarea name="raison" id="raison" rows="3" required placeholder="Pourquoi souhaitez-vous annuler cette commande ?"></textarea>

                    <label for="mot-de-passe">Confirmez avec votre mot de passe</label>
                    <input type="password" name="mot_de_passe" id="mot-de-passe" required autocomplete="current-password">

                    <button type="submit" class="btn-cancel-order">
                        Confirmer l’annulation
                    </button>
                </form>
            </details>
        <?php } ?>
    <?php } ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>