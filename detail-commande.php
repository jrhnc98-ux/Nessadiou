<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../src/Exception.php';
require_once __DIR__ . '/../../src/PHPMailer.php';
require_once __DIR__ . '/../../src/SMTP.php';
session_start();

if (!isset($_SESSION['client'])) {
    header('Location: ' . route_url('login'));
    exit();
}

$dbDisponible = $conn instanceof mysqli;
$commande = null;
$articles = [];
$commandeId = (int) ($_GET['commande_id'] ?? $_GET['id'] ?? 0);
$message = '';
$messageType = '';

if (!isset($_SESSION['csrf_annulation'])) {
    $_SESSION['csrf_annulation'] = bin2hex(random_bytes(32));
}

function envoyer_mail_annulation(array $commande, array $client, string $raison): bool
{
    $mailConfigFile = __DIR__ . '/../../config/mail.php';
    $mailConfig = file_exists($mailConfigFile) ? require $mailConfigFile : [];
    $destinatairePepiniere = $mailConfig['order_email'] ?? getenv('NESSADIOU_ORDER_EMAIL') ?: 'admin@nessadiou.nc';
    $destinataires = array_unique([$destinatairePepiniere, $client['email'] ?? '']);
    $sujet = 'Annulation de la commande n°' . $commande['id'];
    $contenu = "La commande n°{$commande['id']} a été annulée.\n\n";
    $contenu .= 'Client : ' . trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) . "\n";
    $contenu .= 'Date de la commande : ' . date('d/m/Y à H:i', strtotime($commande['date_commande'])) . "\n";
    $contenu .= 'Total : ' . number_format((float) $commande['total'], 0, ',', ' ') . " XPF\n";
    $contenu .= "Raison : $raison\n";

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
            foreach ($destinataires as $destinataire) {
                if ($destinataire !== '') {
                    $mail->addAddress($destinataire);
                }
            }
            $mail->Subject = $sujet;
            $mail->Body = $contenu;
            return $mail->send();
        } catch (Throwable $exception) {
            return false;
        }
    }

    $envoye = true;
    foreach ($destinataires as $destinataire) {
        if ($destinataire !== '') {
            $envoye = @mail(
                $destinataire,
                $sujet,
                $contenu,
                "From: noreply@nessadiou.nc\r\nContent-Type: text/plain; charset=UTF-8\r\n"
            ) && $envoye;
        }
    }
    return $envoye;
}

if ($dbDisponible && $commandeId > 0) {
    $clientId = (int) $_SESSION['client']['id'];
    $stmt = $conn->prepare(
        'SELECT id, statut, date_commande, total, raison_annulation, date_annulation
         FROM commandes
         WHERE id = ? AND client_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $commandeId, $clientId);
    $stmt->execute();
    $commande = $stmt->get_result()->fetch_assoc();

    if ($commande) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && ($_POST['action'] ?? '') === 'annuler_commande') {
            $raisonsAutorisees = [
                "Je me suis trompé dans ma commande",
                "Je souhaite modifier les quantités",
                "Le délai ne me convient plus",
                "Je n'ai plus besoin des produits",
            ];
            $raisonChoisie = trim($_POST['raison'] ?? '');
            $raisonPersonnalisee = trim($_POST['raison_personnalisee'] ?? '');
            $raisonFinale = $raisonChoisie === 'Autre' ? $raisonPersonnalisee : $raisonChoisie;
            $csrfValide = hash_equals($_SESSION['csrf_annulation'], $_POST['csrf_token'] ?? '');
            $confirmationValide = ($_POST['confirmation'] ?? '') === 'oui';
            $motDePasse = $_POST['mot_de_passe'] ?? '';

            $clientStmt = $conn->prepare('SELECT mot_de_passe FROM clients WHERE id = ? LIMIT 1');
            $clientStmt->bind_param('i', $clientId);
            $clientStmt->execute();
            $hash = (string) ($clientStmt->get_result()->fetch_assoc()['mot_de_passe'] ?? '');
            $motDePasseValide = password_verify($motDePasse, $hash)
                || ($hash !== '' && hash_equals($hash, md5($motDePasse)));

            if (!$csrfValide) {
                $message = 'La session de sécurité a expiré. Recharge la page et réessaie.';
                $messageType = 'error';
            } elseif (!in_array($commande['statut'], ['En cours', 'En préparation'], true)) {
                $message = "Cette commande ne peut plus être annulée car elle est déjà {$commande['statut']}.";
                $messageType = 'error';
            } elseif (!$confirmationValide) {
                $message = "Coche la confirmation avant d'annuler.";
                $messageType = 'error';
            } elseif (!$motDePasseValide) {
                $message = 'Le mot de passe est incorrect.';
                $messageType = 'error';
            } elseif (($raisonChoisie !== 'Autre' && !in_array($raisonChoisie, $raisonsAutorisees, true))
                || $raisonFinale === '' || mb_strlen($raisonFinale) > 500) {
                $message = "Sélectionne une raison valide. Le texte personnalisé est limité à 500 caractères.";
                $messageType = 'error';
            } else {
                $update = $conn->prepare(
                    "UPDATE commandes
                     SET statut = 'Annulée', raison_annulation = ?, date_annulation = NOW()
                     WHERE id = ? AND client_id = ? AND statut IN ('En cours', 'En préparation')"
                );
                $update->bind_param('sii', $raisonFinale, $commandeId, $clientId);
                $update->execute();

                if ($update->affected_rows === 1) {
                    $commande['statut'] = 'Annulée';
                    $commande['raison_annulation'] = $raisonFinale;
                    $commande['date_annulation'] = date('Y-m-d H:i:s');
                    $_SESSION['csrf_annulation'] = bin2hex(random_bytes(32));
                    $mailEnvoye = envoyer_mail_annulation($commande, $_SESSION['client'], $raisonFinale);
                    $message = $mailEnvoye
                        ? "La commande a été annulée et l'e-mail a été envoyé."
                        : "La commande a été annulée, mais l'e-mail n'a pas pu être envoyé.";
                    $messageType = $mailEnvoye ? 'success' : 'warning';
                } else {
                    $message = "La commande n'a pas pu être annulée.";
                    $messageType = 'error';
                }
            }
        }

        $stmt = $conn->prepare(
            'SELECT nom_produit, prix_unitaire, quantite, image
             FROM commande_produit
             WHERE commande_id = ?
             ORDER BY id ASC'
        );
        $stmt->bind_param('i', $commandeId);
        $stmt->execute();
        $articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        http_response_code(404);
    }
}

function badge_detail(string $statut): string
{
    return match ($statut) {
        'En préparation' => 'badge-preparation',
        'En livraison' => 'badge-livraison',
        'Livré' => 'badge-livre',
        'Annulée' => 'badge-annulee',
        default => 'badge-encours',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Détail de la commande</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/commandes.css">
</head>
<body class="page-commandes">
<?php require __DIR__ . '/../partials/header.php'; ?>

<main class="container order-detail-page">
    <a class="back-orders" href="<?php echo route_url('admin.suivi_commandes'); ?>">← Retour à mes commandes</a>

    <?php if (!$dbDisponible) { ?>
        <div class="order-alert error">La base de données n'est pas disponible. Vérifie que MySQL est lancé.</div>
    <?php } elseif (!$commande) { ?>
        <section class="order-not-found">
            <div class="order-not-found-code" aria-hidden="true">404</div>
            <h1>Page introuvable</h1>
            <p>La page demandée n’existe pas ou n’est pas accessible.</p>
            <a class="btn" href="<?php echo route_url('home'); ?>">Retour à l’accueil</a>
        </section>
    <?php } else { ?>
        <?php if ($message !== '') { ?>
            <div class="order-alert <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>
        <article class="order-card">
            <header class="order-card-header">
                <div>
                    <h1>Commande n°<?php echo (int) $commande['id']; ?></h1>
                    <time><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></time>
                </div>
                <span class="badge <?php echo badge_detail($commande['statut']); ?>">
                    <?php echo htmlspecialchars($commande['statut'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </header>

            <div class="order-items">
            <?php foreach ($articles as $article) {
                $image = $article['image'];
                if ($image && strpos($image, 'http') !== 0 && strpos($image, '/') !== 0) {
                    $image = BASE_URL . '/assets/images/' . basename($image);
                }
            ?>
                <div class="order-item">
                    <?php if ($image) { ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <?php } ?>
                    <div class="order-item-name">
                        <strong><?php echo htmlspecialchars($article['nom_produit'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo number_format((float) $article['prix_unitaire'], 0, ',', ' '); ?> XPF × <?php echo (int) $article['quantite']; ?></span>
                    </div>
                    <strong><?php echo number_format((float) $article['prix_unitaire'] * (int) $article['quantite'], 0, ',', ' '); ?> XPF</strong>
                </div>
            <?php } ?>
            </div>

            <footer class="order-card-total">
                Total : <strong><?php echo number_format((float) $commande['total'], 0, ',', ' '); ?> XPF</strong>
            </footer>
        </article>

        <?php if ($commande['statut'] === 'Annulée') { ?>
            <section class="cancellation-summary">
                <h2>Commande annulée</h2>
                <p><strong>Raison :</strong> <?php echo nl2br(htmlspecialchars($commande['raison_annulation'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php if ($commande['date_annulation']) { ?>
                    <p><strong>Date :</strong> <?php echo date('d/m/Y à H:i', strtotime($commande['date_annulation'])); ?></p>
                <?php } ?>
            </section>
        <?php } elseif (in_array($commande['statut'], ['En cours', 'En préparation'], true)) { ?>
            <details class="cancel-order">
                <summary>Annuler cette commande</summary>
                <form method="POST" class="cancel-order-form" onsubmit="return confirm('Confirmez-vous définitivement l’annulation de cette commande ?');">
                    <input type="hidden" name="action" value="annuler_commande">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_annulation'], ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="raison">Raison de l’annulation</label>
                    <select name="raison" id="raison" required onchange="document.getElementById('raison-personnalisee').hidden = this.value !== 'Autre';">
                        <option value="">Choisir une raison</option>
                        <option>Je me suis trompé dans ma commande</option>
                        <option>Je souhaite modifier les quantités</option>
                        <option>Le délai ne me convient plus</option>
                        <option>Je n'ai plus besoin des produits</option>
                        <option value="Autre">Autre raison</option>
                    </select>

                    <div id="raison-personnalisee" hidden>
                        <label for="raison-texte">Précisez la raison</label>
                        <textarea name="raison_personnalisee" id="raison-texte" maxlength="500" rows="4"></textarea>
                    </div>

                    <label for="mot-de-passe">Confirmez avec votre mot de passe</label>
                    <input type="password" name="mot_de_passe" id="mot-de-passe" required autocomplete="current-password">

                    <label class="cancel-confirmation">
                        <input
                            type="checkbox"
                            name="confirmation"
                            value="oui"
                            required
                            onchange="document.getElementById('btn-confirmer-annulation').disabled = !this.checked"
                        >
                        Je comprends que cette annulation est définitive.
                    </label>

                    <button type="submit" class="btn-cancel-order" id="btn-confirmer-annulation" disabled>
                        Confirmer l’annulation
                    </button>
                </form>
            </details>
        <?php } ?>
    <?php } ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
