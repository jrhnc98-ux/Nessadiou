<?php
require_once __DIR__ . '/../../config/routes.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>404 — Page introuvable</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/commandes.css">
</head>
<body class="page-commandes">
<?php require __DIR__ . '/../partials/header.php'; ?>

<main class="container">
    <section class="order-not-found">
        <div class="order-not-found-code" aria-hidden="true">404</div>
        <h1>Page introuvable</h1>
        <p>La page que vous recherchez n’existe pas ou a été déplacée.</p>
        <a class="btn" href="<?php echo route_url('home'); ?>">Retour à l’accueil</a>
    </section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
