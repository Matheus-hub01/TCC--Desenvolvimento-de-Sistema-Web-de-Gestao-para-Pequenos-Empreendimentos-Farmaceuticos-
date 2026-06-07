<?php
$host = 'localhost';
$porta = '3050';
$banco = 'C:/BancoFarmacia/Demo/UNIFAR_DEMO.FDB';
$usuario = 'SYSDBA';
$senha = 'masterkey'; // mantenha a mesma senha que funcionou

try {
    $pdo = new PDO(
        "firebird:dbname={$host}/{$porta}:{$banco};charset=UTF8",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco Firebird: ' . htmlspecialchars($e->getMessage()));
}
?>