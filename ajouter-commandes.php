<?php
session_start();
require_once __DIR__ . '/../../config/connexion.php';

// sécurité : client connecté obligatoire
if (!isset($_SESSION['client'])) {
    header("Location: " . route_url('login'));
    exit();
}

$client_id  = $_SESSION['client']['id'];
$produit_id = (int) ($_POST['produit_id'] ?? 0);
$quantite   = max(1, (int) ($_POST['quantite'] ?? 1));

if (!$conn instanceof mysqli) {
    $_SESSION['error'] = "La base de données n'est pas disponible.";
    header("Location: " . route_url('panier'));
    exit();
}

$sql = "INSERT INTO panier (client_id, produit_id, quantite)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $client_id, $produit_id, $quantite);

if ($stmt->execute()) {
    header("Location: " . route_url('panier'));
    exit();
} else {
    $_SESSION['error'] = "Impossible d'ajouter le produit au panier.";
    header("Location: " . route_url('panier'));
    exit();
}
?>
