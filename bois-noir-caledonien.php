<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Bois Noir Calédonien - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Bois Noir Calédonien</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/bois_noir_caledonien.jpg" alt="Bois Noir Calédonien">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Acacia spirorbis</i></p>
    <h2>Description</h2>
    <p>Le bois noir calédonien est un arbre endémique de Nouvelle-Calédonie. Il est particulièrement adapté aux sols pauvres et secs. Il joue un rôle important dans la reforestation et la protection des sols grâce à sa capacité à fixer l'azote. Son bois est utilisé localement pour divers usages.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>