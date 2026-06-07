<?php
session_start();

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    die('Preencha e-mail e senha.');
}

/*
    Por enquanto, como teste simples:
    esse login só verifica se existe um cliente salvo na sessão.
    Depois o ideal é salvar os usuários no banco de dados.
*/

$cliente = $_SESSION['cliente'] ?? null;

if (!$cliente) {
    die('Nenhum usuário cadastrado nesta sessão. Cadastre-se primeiro.');
}

if ($cliente['email'] !== $email) {
    die('E-mail não encontrado.');
}

/*
    ATENÇÃO:
    No cadastrar_usuario.php que te passei antes, a senha ainda não estava sendo salva.
    Então agora precisamos ajustar o cadastro também.
*/

if (($cliente['senha'] ?? '') !== $senha) {
    die('Senha incorreta.');
}

$_SESSION['cliente_logado'] = $cliente;

header('Location: produtos.php?login=sucesso');
exit;