<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Manioc - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Manioc</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/manioc.webp" alt="Manioc">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Manihot esculenta</i></p>
    <h2>Description</h2>
    <p>Le manioc est un arbuste vivace originaire d'Amérique du Sud, largement cultivé dans les zones tropicales pour ses racines comestibles. Il constitue une source majeure de glucides (amidon) pour de nombreuses populations. Résistant et peu exigeant, il pousse facilement dans les sols tropicaux et peut être transformé en farine, semoule, ou consommé directement après cuisson.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-saisonniers">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>