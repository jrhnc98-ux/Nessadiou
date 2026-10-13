<?php
date_default_timezone_set('Pacific/Noumea');

session_start();
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../src/Exception.php';
require_once __DIR__ . '/../../src/PHPMailer.php';
require_once __DIR__ . '/../../src/SMTP.php';

if (!isset($_SESSION['client'])) {
    header('Location: ' . route_url('login'));
    exit();
}

$clientId = (int) $_SESSION['client']['id'];
$dbDisponible = $conn instanceof mysqli;
$erreur = '';

function charger_panier(mysqli $conn, int $clientId): array
{
    $stmt = $conn->prepare(
        "SELECT pa.id AS panier_id, pa.quantite, pa.date_ajout,
                p.id AS produit_id, p.nom, p.prix, p.image
         FROM panier pa
         JOIN produits p ON p.id = pa.produit_id
         WHERE pa.client_id = ?
         ORDER BY pa.date_ajout DESC, pa.id DESC"
    );
    $stmt->bind_param('i', $clientId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function envoyer_recapitulatif_commande(int $commandeId, array $client, array $articles, float $total): bool
{
    $mailConfigFile = __DIR__ . '/../../config/mail.php';
    $mailConfig = file_exists($mailConfigFile) ? require $mailConfigFile : [];
    $destinataire = $mailConfig['order_email'] ?? getenv('NESSADIOU_ORDER_EMAIL') ?: 'admin@nessadiou.nc';
    $sujet = "Nouvelle commande n° $commandeId";
    $message = "Nouvelle commande n° $commandeId\n";
    $message .= 'Date : ' . date('d/m/Y à H:i') . "\n";
    $message .= 'Client : ' . trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) . "\n";
    $message .= 'Email : ' . ($client['email'] ?? '') . "\n\n";
    $message .= "Articles :\n";

    foreach ($articles as $article) {
        $sousTotal = (float) $article['prix'] * (int) $article['quantite'];
        $message .= sprintf(
            "- %s × %d : %s XPF\n",
            $article['nom'],
            $article['quantite'],
            number_format($sousTotal, 0, ',', ' ')
        );
    }

    $message .= "\nTotal : " . number_format($total, 0, ',', ' ') . " XPF\n";
    if ($mailConfig) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = $mailConfig['encryption'] ?? 'tls';
            $mail->Port = (int) ($mailConfig['port'] ?? 587);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name'] ?? 'Pépinière de Nessadiou');
            $mail->addAddress($destinataire);
            $mail->Subject = $sujet;
            $mail->Body = $message;
            return $mail->send();
        } catch (Throwable $exception) {
            return false;
        }
    }

    $headers = "From: noreply@nessadiou.nc\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($destinataire, $sujet, $message, $headers);
}

if ($dbDisponible && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_quantity') {
        $panierId = (int) ($_POST['panier_id'] ?? 0);
        $quantite = max(1, (int) ($_POST['quantite'] ?? 1));
        $stmt = $conn->prepare('UPDATE panier SET quantite = ? WHERE id = ? AND client_id = ?');
        $stmt->bind_param('iii', $quantite, $panierId, $clientId);
        $stmt->execute();
        header('Location: ' . route_url('panier'));
        exit();
    }

    if ($action === 'delete_item') {
        $panierId = (int) ($_POST['panier_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM panier WHERE id = ? AND client_id = ?');
        $stmt->bind_param('ii', $panierId, $clientId);
        $stmt->execute();
        header('Location: ' . route_url('panier'));
        exit();
    }

    if ($action === 'clear_cart') {
        $stmt = $conn->prepare('DELETE FROM panier WHERE client_id = ?');
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        header('Location: ' . route_url('panier'));
        exit();
    }

    if ($action === 'checkout') {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                "SELECT pa.produit_id, pa.quantite, p.nom, p.prix, p.image
                 FROM panier pa
                 JOIN produits p ON p.id = pa.produit_id
                 WHERE pa.client_id = ?
                 FOR UPDATE"
            );
            $stmt->bind_param('i', $clientId);
            $stmt->execute();
            $articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if (!$articles) {
                throw new RuntimeException('Votre panier est vide.');
            }

            $total = 0;
            foreach ($articles as $article) {
                $total += (float) $article['prix'] * (int) $article['quantite'];
            }

            $stmt = $conn->prepare('INSERT INTO commandes (client_id, total) VALUES (?, ?)');
            $stmt->bind_param('id', $clientId, $total);
            $stmt->execute();
            $commandeId = (int) $conn->insert_id;

            $ligne = $conn->prepare(
                'INSERT INTO commande_produit
                 (commande_id, produit_id, nom_produit, prix_unitaire, quantite, image)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($articles as $article) {
                $produitId = (int) $article['produit_id'];
                $nom = $article['nom'];
                $prix = (float) $article['prix'];
                $quantite = (int) $article['quantite'];
                $image = $article['image'];
                $ligne->bind_param('iisdis', $commandeId, $produitId, $nom, $prix, $quantite, $image);
                $ligne->execute();
            }

            $stmt = $conn->prepare('DELETE FROM panier WHERE client_id = ?');
            $stmt->bind_param('i', $clientId);
            $stmt->execute();
            $conn->commit();

            $mailEnvoye = envoyer_recapitulatif_commande($commandeId, $_SESSION['client'], $articles, $total);
            $_SESSION['commande_succes'] = [
                'id' => $commandeId,
                'mail_envoye' => $mailEnvoye,
            ];
            header('Location: ' . route_url('commande.succes'));
            exit();
        } catch (Throwable $exception) {
            $conn->rollback();
            $erreur = $exception->getMessage() === 'Votre panier est vide.'
                ? $exception->getMessage()
                : "La commande n'a pas pu être enregistrée. Réessaie.";
        }
    }
}

$articles = $dbDisponible ? charger_panier($conn, $clientId) : [];
$totalGeneral = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon panier</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/commandes.css">
</head>
<body class="page-commandes">
<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="page-title">
    <h1>Mon panier</h1>
    <p>Vérifiez vos articles avant d'effectuer votre commande.</p>
</section>

<div class="container">
<?php if (!$dbDisponible) { ?>
    <div class="order-alert error">La base de données n'est pas disponible. Vérifie que MySQL est lancé.</div>
<?php } elseif ($erreur !== '') { ?>
    <div class="order-alert error"><?php echo htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<?php if ($dbDisponible && !$articles) { ?>
    <div class="empty-state">
        <div class="empty-cart-illustration" aria-hidden="true">
            <svg viewBox="0 0 96 96" role="img">
                <path d="M18 25h10l7 39h39l8-27H33" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M46 33c0-11 7-18 18-20 1 11-5 19-18 20Z" fill="currentColor" opacity=".9"/>
                <circle cx="42" cy="75" r="5" fill="currentColor"/>
                <circle cx="70" cy="75" r="5" fill="currentColor"/>
            </svg>
        </div>
        <span class="empty-cart-kicker">Votre sélection vous attend</span>
        <h2>Votre panier est encore vide</h2>
        <p>Découvrez nos arbres, fruitiers et plantes adaptés aux jardins de Nouvelle-Calédonie.</p>
        <a href="<?php echo route_url('catalogue.arbres'); ?>" class="empty-cart-button">Découvrir nos produits <span aria-hidden="true">→</span></a>
    </div>
<?php } elseif ($articles) { ?>
    <form method="POST" class="clear-cart-form" onsubmit="return confirm('Vider tout le panier ?');">
        <input type="hidden" name="action" value="clear_cart">
        <button type="submit" class="btn-danger">Vider le panier</button>
    </form>

    <div class="table-scroll">
    <table class="table-panier">
        <thead>
        <tr>
            <th>Produit</th>
            <th>Quantité</th>
            <th>Prix unitaire</th>
            <th>Sous-total</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($articles as $article) {
            $sousTotal = (float) $article['prix'] * (int) $article['quantite'];
            $totalGeneral += $sousTotal;
            $image = $article['image'];
            if ($image && strpos($image, 'http') !== 0 && strpos($image, '/') !== 0) {
                $image = BASE_URL . '/assets/images/' . basename($image);
            }
        ?>
        <tr>
            <td>
                <span class="cell-product">
                    <?php if ($image) { ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <?php } ?>
                    <?php echo htmlspecialchars($article['nom'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </td>
            <td>
                <form method="POST" class="cart-action-form">
                    <input type="hidden" name="action" value="update_quantity">
                    <input type="hidden" name="panier_id" value="<?php echo (int) $article['panier_id']; ?>">
                    <input type="number" name="quantite" min="1" value="<?php echo (int) $article['quantite']; ?>">
                    <button type="submit">Modifier</button>
                </form>
            </td>
            <td><?php echo number_format((float) $article['prix'], 0, ',', ' '); ?> XPF</td>
            <td class="total"><?php echo number_format($sousTotal, 0, ',', ' '); ?> XPF</td>
            <td>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_item">
                    <input type="hidden" name="panier_id" value="<?php echo (int) $article['panier_id']; ?>">
                    <button type="submit" class="btn-danger">Supprimer</button>
                </form>
            </td>
        </tr>
        <?php } ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3"><strong>Total du panier</strong></td>
            <td class="total"><?php echo number_format($totalGeneral, 0, ',', ' '); ?> XPF</td>
            <td></td>
        </tr>
        </tfoot>
    </table>
    </div>

    <form method="POST" class="checkout-form">
        <input type="hidden" name="action" value="checkout">
        <button type="submit" class="btn-checkout">Effectuer la commande</button>
    </form>
<?php } ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
