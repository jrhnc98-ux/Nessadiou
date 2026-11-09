<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Bois Noir Haïti - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Bois Noir Haïti</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/samanea_saman.jpg" alt="Bois Noir Haïti">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Samanea saman</i></p>
    <h2>Description</h2>
    <p>Originaire d'Amérique centrale et des Caraïbes, cet arbre est connu pour son bois riche en pigments naturels. Il a longtemps été utilisé pour la teinture textile. C'est un arbre résistant qui peut s'adapter à différents types de sols tropicaux.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>