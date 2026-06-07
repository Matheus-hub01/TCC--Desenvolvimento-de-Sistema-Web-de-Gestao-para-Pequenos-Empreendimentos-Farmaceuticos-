<?php
session_start();

$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $sobrenome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
    die('Preencha todos os campos.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('E-mail inválido.');
}

if ($senha !== $confirmarSenha) { 
    die('As senhas não conferem.');
}

$_SESSION['cliente'] = [
    'nome' => $nome,
    'sobrenome' => $sobrenome,
    'email' => $email,
    'senha' => $senha
];

header('Location: produtos.php?cadastro=sucesso');
exit;