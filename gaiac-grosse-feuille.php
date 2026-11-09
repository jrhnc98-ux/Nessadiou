<?php require_once __DIR__ . '/../../config/routes.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gaïac Grosse Feuille - Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fiche-produit.css">
</head>
<body class="fiche-produit">
  <header>
    <h1>Gaïac Grosse Feuille</h1>
  </header>
  <img src="<?php echo BASE_URL; ?>/assets/images/Guaiacum-sanctum-Lignum-Vitae.jpg" alt="Gaïac Grosse Feuille">
  <div class="content">
    <h2>Nom scientifique</h2>
    <p><i>Guaiacum officinale</i></p>
    <h2>Description</h2>
    <p>Le gaïac est un arbre tropical originaire des Caraïbes et d'Amérique centrale. Il est particulièrement connu pour son bois extrêmement dur, dense et résistant, souvent appelé « bois de vie ». Ce bois possède des propriétés naturelles d'auto-lubrification, ce qui le rend très recherché pour des usages techniques et artisanaux. Le gaïac pousse lentement et peut atteindre une dizaine de mètres de hauteur. Il produit de belles fleurs bleues ou violettes, ainsi que des fruits jaunes caractéristiques. En raison de sa surexploitation passée, cet arbre est aujourd'hui protégé dans plusieurs régions du monde.</p>
    <a href="<?php echo BASE_URL; ?>/router.php?route=catalogue/nos-arbres">⬅ Retour</a>
  </div>
  <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>