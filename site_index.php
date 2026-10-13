<?php
session_start();
require_once __DIR__ . '/../../config/routes.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Pépinière de Nessadiou</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>

<body class="home-page">

<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="hero">
  <div class="hero-content">
    <h1>Plantation et Nature</h1>
    <p>Plants locaux, fruitiers et espèces adaptées au climat de Nouvelle-Calédonie.</p>
    <a href="#produits" class="btn-white">Découvrir les produits</a>
  </div>
</section>

<div class="container">

<section class="section">
  <h2>À propos de la pépinière</h2>
  <p>
    La Pépinière de Nessadiou est un lieu dédié à l'agriculture durable situé avant Bourail.
    Nous produisons des plants locaux adaptés au climat de Nouvelle-Calédonie,
    dans le respect de l’environnement.
  </p>
</section>

<section class="section" id="produits">
  <div class="products-heading">
    <div>
      <span>Nos collections</span>
      <h2>Des plantes pour chaque jardin</h2>
    </div>
    <p>Découvrez une sélection cultivée localement et adaptée au climat de Nouvelle-Calédonie.</p>
  </div>

  <div class="cards product-family-grid">

    <article class="card product-family-card">
      <div class="product-family-image">
        <img src="<?php echo BASE_URL; ?>/assets/images/arbres.jpg" alt="Arbres cultivés à la pépinière">
        <span>Arbres locaux</span>
      </div>
      <div class="product-family-content">
        <h3>Arbres</h3>
        <p>Des espèces robustes pour créer de l’ombre et enrichir durablement votre jardin.</p>
        <a href="<?php echo route_url('catalogue.arbres'); ?>" class="product-family-link">Explorer la collection <span aria-hidden="true">→</span></a>
      </div>
    </article>

    <article class="card product-family-card">
      <div class="product-family-image">
        <img src="<?php echo BASE_URL; ?>/assets/images/arbres fruitiers.webp" alt="Arbres fruitiers tropicaux">
        <span>Saveurs tropicales</span>
      </div>
      <div class="product-family-content">
        <h3>Arbres fruitiers</h3>
        <p>Des fruitiers généreux pour récolter chez vous des produits frais et savoureux.</p>
        <a href="<?php echo route_url('catalogue.fruitiers'); ?>" class="product-family-link">Explorer la collection <span aria-hidden="true">→</span></a>
      </div>
    </article>

    <article class="card product-family-card">
      <div class="product-family-image">
        <img src="<?php echo BASE_URL; ?>/assets/images/moringa.webp" alt="Plantes saisonnières de Nouvelle-Calédonie">
        <span>Au rythme des saisons</span>
      </div>
      <div class="product-family-content">
        <h3>Plantes saisonnières</h3>
        <p>Des variétés choisies selon la saison pour un potager vivant toute l’année.</p>
        <a href="<?php echo route_url('catalogue.saisonniers'); ?>" class="product-family-link">Explorer la collection <span aria-hidden="true">→</span></a>
      </div>
    </article>

  </div>
</section>

<section class="section" id="contact">
  <h2>Contact</h2>
  <p>Email : pascalrobertmph@lagoon.nc</p>
  <p>Téléphone : +687 75 14 66</p>
</section>

<!-- Carte Google Maps -->
<section class="section" id="carte">
  <h2>Notre terrain</h2>
  <div class="carte-container">
    <iframe src="https://www.google.com/maps?saddr=Place+des+Cocotiers%2C+Noum%C3%A9a%2C+Nouvelle-Cal%C3%A9donie&amp;daddr=Route+de+la+Pointe+de+Nessadiou%2C+Nouvelle-Cal%C3%A9donie&amp;output=embed"
      class="map-frame"
      allowfullscreen=""
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade">
    </iframe>
  </div>
</section>

</div> <!-- fin du container -->

<?php require __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>
