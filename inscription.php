<?php
// Inclut le fichier de connexion à la base de données (fournit la variable $conn)
require_once __DIR__ . '/../../config/connexion.php';

$message = '';
$messageClass = '';
 
// Vérifie que le formulaire a bien été soumis en méthode POST
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST") {
 
    // Récupère les champs du formulaire envoyés par l'utilisateur
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password  = $_POST['password'] ?? '';
 
    if (!$conn instanceof mysqli) {
        $message = "La base de données n'est pas disponible. Vérifie que MySQL est lancé et que la base nessadiou existe.";
        $messageClass = 'erreur';
    } elseif ($nom === '' || $prenom === '' || $email === '' || $telephone === '' || $password === '') {
        $message = "Tous les champs sont obligatoires.";
        $messageClass = 'erreur';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
        $messageClass = 'erreur';
    } else {
        // Hache le mot de passe avec l'algorithme bcrypt (PASSWORD_DEFAULT) — ne jamais stocker en clair
        $mdp = password_hash($password, PASSWORD_DEFAULT);

        $sql = $conn->prepare("INSERT INTO clients (nom, prenom, email, telephone, mot_de_passe) VALUES (?, ?, ?, ?, ?)");

        if (!$sql) {
            $message = "Impossible de préparer l'inscription. Vérifie que la table clients existe.";
            $messageClass = 'erreur';
        } else {
            $sql->bind_param("sssss", $nom, $prenom, $email, $telephone, $mdp);

            if ($sql->execute()) {
                header("Location: " . route_url('login') . "?inscription=ok");
                exit();
            }

            if ($conn->errno === 1062) {
                $message = "Un compte existe déjà avec cet email.";
            } else {
                $message = "Erreur lors de la création du compte : " . $conn->error;
            }
            $messageClass = 'erreur';
        }
    }
}
?>
 
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Inscription</title>
<!-- Lien vers la feuille de style externe commune -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
 
<body class="auth-page login-page signup-page">

<a class="btn-retour auth-back" href="<?php echo route_url('home'); ?>">← Retour à l'accueil</a>

<main class="login-shell signup-shell">
  <section class="login-visual signup-visual">
    <div class="login-visual-content">
      <span class="login-eyebrow">Pépinière de Nessadiou</span>
      <h1>Faites grandir vos projets</h1>
      <p>Créez votre espace pour composer votre panier et suivre simplement toutes vos commandes.</p>
      <ul class="signup-benefits">
        <li><span>✓</span> Des plantes cultivées localement</li>
        <li><span>✓</span> Un suivi clair de vos commandes</li>
        <li><span>✓</span> Une sélection adaptée à votre jardin</li>
      </ul>
    </div>
  </section>

  <section class="form login-card signup-card">
    <div class="login-card-heading">
      <span class="login-leaf" aria-hidden="true">🌱</span>
      <h2>Créer votre compte</h2>
      <p>Quelques informations suffisent pour commencer</p>
    </div>

    <?php if ($message !== '') { ?>
      <p class="message <?php echo $messageClass; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <form method="POST">
      <div class="signup-name-row">
        <div>
          <label for="signup-nom">Nom</label>
          <input id="signup-nom" type="text" name="nom" placeholder="Votre nom" autocomplete="family-name" value="<?php echo htmlspecialchars($nom ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div>
          <label for="signup-prenom">Prénom</label>
          <input id="signup-prenom" type="text" name="prenom" placeholder="Votre prénom" autocomplete="given-name" value="<?php echo htmlspecialchars($prenom ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
      </div>

      <label for="signup-email">Adresse e-mail</label>
      <input id="signup-email" type="email" name="email" placeholder="vous@exemple.nc" autocomplete="email" value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

      <label for="signup-telephone">Téléphone</label>
      <input id="signup-telephone" type="tel" name="telephone" placeholder="Ex. 75 14 66" autocomplete="tel" value="<?php echo htmlspecialchars($telephone ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

      <label for="signup-password">Mot de passe</label>
      <input id="signup-password" type="password" name="password" placeholder="Choisissez un mot de passe" autocomplete="new-password" required>

      <button type="submit">Créer mon compte</button>
    </form>

    <p class="signup-prompt">Déjà un compte ? <a href="<?php echo route_url('login'); ?>">Se connecter</a></p>
  </section>
</main>
 
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
