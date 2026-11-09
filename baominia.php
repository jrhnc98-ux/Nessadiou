<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Baominia - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Baominia</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/baominia.jpg" alt="Baominia">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Bauhinia spp</i></p>
    <h2>Description</h2>
    <p>Le Baominia est un arbre ornemental apprécié pour ses belles fleurs colorées et décoratives. Il pousse bien dans les climats tropicaux et subtropicaux. Cet arbre est souvent utilisé pour embellir les jardins et les espaces publics.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>