<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Burao - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Burao</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/burao.webp" alt="Burao">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Hibiscus tiliaceus</i></p>
    <h2>Description</h2>
    <p>Le Burao est un arbre tropical que l'on trouve souvent en bord de mer. Il est apprécié pour sa résistance aux vents et aux sols salins. Ses feuilles et son bois sont utilisés dans certaines pratiques artisanales, et il contribue à la stabilisation des sols côtiers.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>