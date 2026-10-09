<?php
/**
 * Conexão com o Banco de Dados Firebird
 * As credenciais e configurações são obrigatoriamente lidas do arquivo .env
 */

$caminhosEnv = [
    dirname(__DIR__) . '/.env',
    __DIR__ . '/.env'
];

$env = [];
$envEncontrado = false;

foreach ($caminhosEnv as $caminho) {
    if (file_exists($caminho)) {
        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }
            if (str_contains($linha, '=')) {
                [$chave, $valor] = explode('=', $linha, 2);
                $env[trim($chave)] = trim($valor, " \t\n\r\0\x0B\"'");
            }
        }
        $envEncontrado = true;
        break;
    }
}

if (!$envEncontrado) {
    die('Arquivo .env não encontrado. Crie o arquivo .env a partir do modelo .env.exemplo.');
}

$host    = $env['DB_HOST']     ?? 'localhost';
$porta   = $env['DB_PORT']     ?? '3050';
$banco   = $env['DB_DATABASE'] ?? '';
$usuario = $env['DB_USER']     ?? '';
$senha   = $env['DB_PASSWORD'] ?? '';
$charset = $env['DB_CHARSET']  ?? 'UTF8';

if ($banco === '' || $usuario === '' || $senha === '') {
    die('Configurações de banco incompletas no arquivo .env. Certifique-se de definir DB_DATABASE, DB_USER e DB_PASSWORD.');
}

$opcoes = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

$pdo = null;

// Tenta conexão via rede (TCP) se houver host configurado
if (!empty($host)) {
    try {
        $dsnTcp = "firebird:dbname={$host}/{$porta}:{$banco};charset={$charset}";
        $pdo = new PDO($dsnTcp, $usuario, $senha, $opcoes);
    } catch (PDOException $e) {
        $pdo = null;
    }
}

// Fallback para conexão direta / embedded local
if ($pdo === null) {
    try {
        $dsnDireto = "firebird:dbname={$banco};charset={$charset}";
        $pdo = new PDO($dsnDireto, $usuario, $senha, $opcoes);
    } catch (PDOException $e) {
        $msg = 'Erro ao conectar ao banco Firebird: ' . $e->getMessage();
        error_log($msg);
        die($msg);
    }
}
?>
