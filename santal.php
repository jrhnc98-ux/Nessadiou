<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Santal - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Santal</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/santal.webp" alt="Santal">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Santalum austrocaledonicum</i></p>
    <h2>Description</h2>
    <p>Le santal est un arbre précieux de Nouvelle-Calédonie. Il est connu pour son bois parfumé utilisé en parfumerie et en médecine traditionnelle. Il pousse principalement dans les zones sèches.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>