<?php
session_start();

unset($_SESSION['usuario_logado']);
unset($_SESSION['cliente_logado']);
unset($_SESSION['cliente']);

header('Location: produtos.php?logout=sucesso');
exit;