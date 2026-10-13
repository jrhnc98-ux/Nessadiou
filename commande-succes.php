<?php
session_start();
require_once __DIR__ . '/../../config/routes.php';

if (!isset($_SESSION['client'])) {
    header('Location: ' . route_url('login'));
    exit();
}

$confirmation = $_SESSION['commande_succes'] ?? null;
unset($_SESSION['commande_succes']);

if (!$confirmation) {
    header('Location: ' . route_url('admin.suivi_commandes'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Commande effectuée</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/commandes.css">
</head>
<body class="page-commandes">
<?php require __DIR__ . '/../partials/header.php'; ?>

<main class="order-success">
    <div class="success-icon" aria-hidden="true">✓</div>
    <h1>Commande effectuée avec succès</h1>
    <p class="success-summary">Votre commande <strong>n°<?php echo (int) $confirmation['id']; ?></strong> a bien été enregistrée.</p>
    <?php if (!$confirmation['mail_envoye']) { ?>
        <p class="mail-warning">
            <span aria-hidden="true">ⓘ</span>
            L’e-mail de confirmation n’a pas pu être envoyé, mais votre commande est bien enregistrée.
        </p>
    <?php } ?>
    <div class="success-actions">
        <a class="btn" href="<?php echo route_url('admin.suivi_commandes'); ?>">Voir mes commandes</a>
        <a class="btn btn-secondary" href="<?php echo route_url('catalogue.arbres'); ?>">Continuer mes achats</a>
    </div>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
