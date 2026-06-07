<?php
session_start();

unset($_SESSION['cliente_logado']);

header('Location: produtos.php');
exit;