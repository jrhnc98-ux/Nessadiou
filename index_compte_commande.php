<?php
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$commandeId = (int) ($_GET['id'] ?? 0);

if ($commandeId > 0) {
    $_GET['commande_id'] = $commandeId;
    require dirname(__DIR__, 2) . '/pages/admin/detail-commande.php';
    exit();
}

// Compatibilité avec les serveurs sur lesquels la réécriture d'URL est active.
if (preg_match('#/compte/commande/([1-9][0-9]*)/?$#', $requestPath, $matches)) {
    $_GET['commande_id'] = (int) $matches[1];
    require dirname(__DIR__, 2) . '/pages/admin/detail-commande.php';
    exit();
}

http_response_code(404);
echo 'Commande introuvable';
