<?php

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function catalogue_image_url($image)
{
    $image = (string) $image;

    if (strpos($image, 'http') === 0 || strpos($image, '/') === 0) {
        return $image;
    }

    return BASE_URL . '/assets/images/' . basename($image);
}

function gerer_actions_catalogue($conn, $redirectUrl)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'commander') {
        if (!isset($_SESSION['client'])) {
            $_SESSION['error'] = 'Vous devez être connecté pour commander.';
            header('Location: ' . $redirectUrl);
            exit();
        }

        $clientId = (int) $_SESSION['client']['id'];
        $produitId = (int) ($_POST['produit_id'] ?? 0);
        $quantite = max(1, (int) ($_POST['quantite'] ?? 1));

        $produitStmt = $conn->prepare('SELECT id, nom, prix FROM produits WHERE id = ?');
        $produitStmt->bind_param('i', $produitId);
        $produitStmt->execute();
        $produit = $produitStmt->get_result()->fetch_assoc();

        if (!$produit) {
            $_SESSION['error'] = 'Produit introuvable.';
            header('Location: ' . $redirectUrl);
            exit();
        }

        $stmt = $conn->prepare(
            'INSERT INTO panier (client_id, produit_id, quantite)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)'
        );
        $stmt->bind_param('iii', $clientId, $produitId, $quantite);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Produit ajouté au panier !';
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout au panier.";
        }

        header('Location: ' . $redirectUrl);
        exit();
    }

    if (($_GET['action'] ?? '') === 'delete') {
        if (!isset($_SESSION['client']) || ($_SESSION['client']['role'] ?? '') !== 'admin') {
            $_SESSION['error'] = 'Accès refusé.';
            header('Location: ' . $redirectUrl);
            exit();
        }

        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM produits WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $_SESSION['success'] = 'Produit supprimé.';
        header('Location: ' . $redirectUrl);
        exit();
    }
}

function afficher_messages_catalogue()
{
    foreach (['success' => 'popup-success', 'error' => 'popup-error'] as $key => $class) {
        if (!isset($_SESSION[$key])) {
            continue;
        }

        echo '<div class="' . $class . '">' . e($_SESSION[$key]) . '</div>';
        unset($_SESSION[$key]);
    }
}

function afficher_carte_produit($row, $pageUrl)
{
    $prix = isset($row['prix']) ? (float) $row['prix'] : 0;
    ?>
    <div class="card">
      <img src="<?php echo e(catalogue_image_url($row['image'] ?? '')); ?>" alt="<?php echo e($row['nom'] ?? 'Produit'); ?>">
      <h3><?php echo e($row['nom'] ?? 'Produit'); ?></h3>
      <p><?php echo e($row['description'] ?? ''); ?></p>

      <?php if ($prix > 0) { ?>
        <strong><?php echo e(number_format($prix, 0, ',', ' ')); ?> XPF</strong>
      <?php } ?>

      <?php if (isset($_SESSION['client'])) { ?>
        <form method="POST" class="commande-form">
          <input type="hidden" name="action" value="commander">
          <input type="hidden" name="produit_id" value="<?php echo e($row['id']); ?>">
          <input type="number" name="quantite" min="1" value="1" required>
          <button type="submit">Commander</button>
        </form>
      <?php } ?>

      <?php if (isset($_SESSION['client']['role']) && $_SESSION['client']['role'] === 'admin') { ?>
        <a class="delete-link"
           href="<?php echo e($pageUrl); ?>?action=delete&id=<?php echo e($row['id']); ?>"
           onclick="return confirm('Supprimer ce produit ?');">
          Supprimer
        </a>
      <?php } ?>
    </div>
    <?php
}
?>
