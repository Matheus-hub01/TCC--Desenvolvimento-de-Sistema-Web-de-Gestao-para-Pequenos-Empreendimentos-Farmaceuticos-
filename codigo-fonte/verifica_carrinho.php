<?php
session_start();

$carrinho = $_SESSION['carrinho'] ?? [];

header('Content-Type: application/json');

echo json_encode([
    'vazio' => empty($carrinho)
]);
?>
