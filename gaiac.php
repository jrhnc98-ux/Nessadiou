<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gaïac - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Gaïac</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/gaiac.jpg" alt="Gaïac">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Guaiacum sanctum</i></p>
    <h2>Description</h2>
    <p>Le Gaïac est un arbre réputé pour son bois extrêmement dur et dense. Il est utilisé dans la fabrication d'objets résistants et durables. Cet arbre pousse lentement et est souvent protégé en raison de sa rareté.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>