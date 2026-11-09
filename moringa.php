<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Moringa - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Moringa</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/moringa.webp" alt="Moringa">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Moringa oleifera</i></p>
    <h2>Description</h2>
    <p>Le Moringa est un arbre à croissance rapide, originaire des régions tropicales. Il est extrêmement résistant à la sécheresse et s'adapte à des sols pauvres. Ses feuilles, ses graines et ses fleurs sont très riches en nutriments et possèdent de nombreuses vertus médicinales et nutritionnelles. Il est souvent appelé "l'arbre de vie".</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-saisonniers">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>