<?php
require_once __DIR__ . '/../../config/routes.php';

session_start();
session_unset();
$redirect =  route_url('home');

session_destroy();

header('Location: ' . $redirect);
exit();
?>
