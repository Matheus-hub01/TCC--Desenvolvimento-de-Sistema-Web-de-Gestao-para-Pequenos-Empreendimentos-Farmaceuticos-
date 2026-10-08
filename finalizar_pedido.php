<?php

session_start();

date_default_timezone_set('America/Sao_Paulo');

if (empty($_SESSION['cliente_logado'])) {
    header('Location: carrinho.php?login=necessario');
    exit;
}

$carrinho = $_SESSION['carrinho'] ?? [];

if (empty($carrinho)) {
    header('Location: carrinho.php');
    exit;
}

$cliente = $_SESSION['cliente_logado'];

$nome = trim(
    (string) ($cliente['nome'] ?? '')
);

$sobrenome = trim(
    (string) ($cliente['sobrenome'] ?? '')
);

$nomeCliente = trim(
    $nome . ' ' . $sobrenome
);


if ($nomeCliente === '') {

    $clienteCadastro = $_SESSION['cliente'] ?? [];

    $nome = trim(
        (string) ($clienteCadastro['nome'] ?? '')
    );

    $sobrenome = trim(
        (string) ($clienteCadastro['sobrenome'] ?? '')
    );

    $nomeCliente = trim(
        $nome . ' ' . $sobrenome
    );
}



/*
|--------------------------------------------------------------------------
| Monta os itens do pedido
|--------------------------------------------------------------------------
*/

$itensPedido = [];
$totalPedido = 0;

foreach ($carrinho as $chave => $item) {

    $codigo = (string) (
        $item['codigo']
        ?? $chave
    );

    $nome = (string) (
        $item['nome']
        ?? 'Produto'
    );

    $quantidade = max(
        1,
        (int) (
            $item['quantidade']
            ?? 1
        )
    );

    $preco = (float) (
        $item['preco']
        ?? 0
    );

    $subtotal = $quantidade * $preco;

    $totalPedido += $subtotal;

    $itensPedido[] = [
        'codigo' => $codigo,
        'nome' => $nome,
        'quantidade' => $quantidade,
        'preco' => $preco,
        'subtotal' => $subtotal
    ];
}


/*
|--------------------------------------------------------------------------
| Número temporário do pedido
|--------------------------------------------------------------------------
|
| Depois vamos substituir isso pelo ID real do banco.
|
*/

if (!isset($_SESSION['proximo_numero_pedido'])) {
    $_SESSION['proximo_numero_pedido'] = 1042;
}

$numeroPedido = $_SESSION['proximo_numero_pedido'];

$_SESSION['proximo_numero_pedido']++;


/*
|--------------------------------------------------------------------------
| Salva o pedido na sessao
|--------------------------------------------------------------------------
*/

$_SESSION['ultimo_pedido'] = [
    'numero' => $numeroPedido,
    'cliente' => $nomeCliente,
    'data' => date('d/m/Y H:i'),
    'status' => 'Aguardando preparação',
    'itens' => $itensPedido,
    'total' => $totalPedido
];


/*
|--------------------------------------------------------------------------
| Limpa o carrinho
|--------------------------------------------------------------------------
*/

$_SESSION['carrinho'] = [];


/*
|--------------------------------------------------------------------------
| Vai para a confirmacao
|--------------------------------------------------------------------------
*/

header('Location: confirmacao_pedido.php');
exit;