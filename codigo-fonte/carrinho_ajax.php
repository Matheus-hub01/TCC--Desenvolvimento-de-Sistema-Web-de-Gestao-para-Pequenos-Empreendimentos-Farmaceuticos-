<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

function respostaCarrinho(): void
{
    $carrinho = $_SESSION['carrinho'] ?? [];

    $totalItens = 0;
    $subtotal = 0;

    foreach ($carrinho as $item) {
        $quantidade = (int) ($item['quantidade'] ?? 1);
        $preco = (float) ($item['preco'] ?? 0);

        $totalItens += $quantidade;
        $subtotal += $quantidade * $preco;
    }

    echo json_encode([
        'sucesso' => true,
        'carrinho' => array_values($carrinho),
        'totalItens' => $totalItens,
        'subtotal' => $subtotal
    ]);
    exit;
}

if ($acao === 'listar') {
    respostaCarrinho();
}

if ($acao === 'adicionar') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nome = trim($_POST['nome'] ?? 'Produto');
    $preco = (float) ($_POST['preco'] ?? 0);
    $imagem = trim($_POST['imagem'] ?? 'img/produtos/sem-imagem.png');

    if ($codigo === '') {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Produto inválido.'
        ]);
        exit;
    }

    if (isset($_SESSION['carrinho'][$codigo])) {
        $_SESSION['carrinho'][$codigo]['quantidade']++;
    } else {
        $_SESSION['carrinho'][$codigo] = [
            'codigo' => $codigo,
            'nome' => $nome,
            'preco' => $preco,
            'imagem' => $imagem,
            'quantidade' => 1
        ];
    }

    respostaCarrinho();
}

if ($acao === 'alterar') {
    $codigo = trim($_POST['codigo'] ?? '');
    $quantidade = (int) ($_POST['quantidade'] ?? 1);

    if ($codigo !== '' && isset($_SESSION['carrinho'][$codigo])) {
        if ($quantidade <= 0) {
            unset($_SESSION['carrinho'][$codigo]);
        } else {
            $_SESSION['carrinho'][$codigo]['quantidade'] = $quantidade;
        }
    }

    respostaCarrinho();
}

if ($acao === 'remover') {
    $codigo = trim($_POST['codigo'] ?? '');

    if ($codigo !== '' && isset($_SESSION['carrinho'][$codigo])) {
        unset($_SESSION['carrinho'][$codigo]);
    }

    respostaCarrinho();
}

echo json_encode([
    'sucesso' => false,
    'mensagem' => 'Ação inválida.'
]);