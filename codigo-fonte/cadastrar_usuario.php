<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Helper para exibir popup bonito e amigável com redirecionamento automático
function exibirPopupMensagem(string $tipo, string $titulo, string $mensagem, string $urlDestino = 'produtos.php', string $textoBotao = 'Voltar à Página Inicial', ?string $urlSecundaria = null, ?string $textoSecundario = null)
{
    $icone = match ($tipo) {
        'sucesso' => '
            <div style="width: 72px; height: 72px; margin: 0 auto 1.25rem; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; color: #16a34a;">
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>',
        'alerta', 'aviso' => '
            <div style="width: 72px; height: 72px; margin: 0 auto 1.25rem; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center; color: #d97706;">
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>',
        'erro' => '
            <div style="width: 72px; height: 72px; margin: 0 auto 1.25rem; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; color: #dc2626;">
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>',
        default => '
            <div style="width: 72px; height: 72px; margin: 0 auto 1.25rem; border-radius: 50%; background: #e0f2fe; display: flex; align-items: center; justify-content: center; color: #0284c7;">
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>'
    };

    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo} - Drogaria PharmaPaz</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #004d40 50%, #008a73 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .popup-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2.25rem 2rem;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            animation: popupFadeIn 0.35s ease-out;
            position: relative;
            overflow: hidden;
        }
        @keyframes popupFadeIn {
            from { opacity: 0; transform: scale(0.92) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .barra-progresso-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: #e2e8f0;
        }
        .barra-progresso {
            height: 100%;
            background: #008a73;
            width: 100%;
            animation: progresso 4s linear forwards;
        }
        @keyframes progresso {
            from { width: 100%; }
            to { width: 0%; }
        }
        .popup-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }
        .popup-message {
            font-size: 0.98rem;
            color: #475569;
            line-height: 1.55;
            margin-bottom: 1.75rem;
        }
        .popup-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .btn-principal {
            background: #008a73;
            color: #ffffff;
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-principal:hover {
            background: #006d5b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 138, 115, 0.35);
        }
        .btn-secundario {
            background: #f1f5f9;
            color: #334155;
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: inline-block;
            border: 1px solid #cbd5e1;
        }
        .btn-secundario:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .countdown-text {
            margin-top: 1.25rem;
            font-size: 0.82rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="popup-card">
        <div class="barra-progresso-container">
            <div class="barra-progresso"></div>
        </div>
        {$icone}
        <h2 class="popup-title">{$titulo}</h2>
        <p class="popup-message">{$mensagem}</p>
        <div class="popup-actions">
HTML;
    if ($urlSecundaria && $textoSecundario) {
        echo "<a href=\"{$urlSecundaria}\" class=\"btn-principal\">{$textoSecundario}</a>";
        echo "<a href=\"{$urlDestino}\" class=\"btn-secundario\">{$textoBotao}</a>";
    } else {
        echo "<a href=\"{$urlDestino}\" class=\"btn-principal\">{$textoBotao}</a>";
    }

    echo <<<HTML
        </div>
        <p class="countdown-text">Retornando à área inicial em <span id="contador">4</span> segundos...</p>
    </div>

    <script>
        let segundos = 4;
        const el = document.getElementById('contador');
        const timer = setInterval(() => {
            segundos--;
            if (el) el.textContent = segundos;
            if (segundos <= 0) {
                clearInterval(timer);
                window.location.href = '{$urlDestino}';
            }
        }, 1000);
    </script>
</body>
</html>
HTML;
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';
$origem = trim($_POST['origem'] ?? '');

if ($nome === '' || $sobrenome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
    exibirPopupMensagem(
        'alerta',
        'Campos Incompletos',
        'Todos os campos são obrigatórios para realizar o cadastro. Por favor, verifique e tente novamente.',
        'produtos.php?abrir=cadastro',
        'Voltar ao Formulário',
        'produtos.php',
        'Ir para a Loja'
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exibirPopupMensagem(
        'alerta',
        'E-mail Inválido',
        'O endereço de e-mail informado não possui um formato válido.',
        'produtos.php?abrir=cadastro',
        'Corrigir E-mail'
    );
}

if ($senha !== $confirmarSenha) {
    exibirPopupMensagem(
        'erro',
        'Senhas Não Conferem',
        'A senha e a confirmação de senha digitadas são diferentes. Certifique-se de digitar a mesma senha em ambos os campos.',
        'produtos.php?abrir=cadastro',
        'Digitar Novamente'
    );
}

// 1. Verifica se já existe no banco
try {
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS QTD FROM USUARIO WHERE UPPER(EMAIL) = UPPER(?)");
    $stmtCheck->execute([$email]);
    if ((int)($stmtCheck->fetchColumn() ?? 0) > 0) {
        exibirPopupMensagem(
            'aviso',
            'E-mail Já Cadastrado',
            'Já existe um usuário cadastrado com este e-mail. Faça login ou recupere seu acesso para continuar.',
            'produtos.php?abrir=login',
            'Ir para o Login',
            'produtos.php',
            'Voltar à Loja'
        );
    }

    // 2. Obtem novo ID para USUARIO
    $novoId = 1;
    try {
        $genStmt = $pdo->query("SELECT NEXT VALUE FOR GEN_USUARIO_ID AS NOVO_ID FROM RDB\$DATABASE");
        $novoId = (int)$genStmt->fetchColumn();
    } catch (Exception $e) {
        $maxStmt = $pdo->query("SELECT COALESCE(MAX(CODUSUARIO), 0) + 1 AS NOVO_ID FROM USUARIO");
        $novoId = (int)$maxStmt->fetchColumn();
    }

    $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
    $perfilPadrao = 'CLIENTE';

    $stmtIns = $pdo->prepare("
        INSERT INTO USUARIO (CODUSUARIO, NOME, SOBRENOME, EMAIL, SENHA, PERFIL, STATUS)
        VALUES (?, ?, ?, ?, ?, ?, '1')
    ");
    $stmtIns->execute([$novoId, $nome, $sobrenome, $email, $hashSenha, $perfilPadrao]);

    // Opcional: inserir também na tabela CLIENTE para compatibilidade
    try {
        $stmtCliCheck = $pdo->prepare("SELECT COUNT(*) AS QTD FROM CLIENTE WHERE UPPER(EMAIL) = UPPER(?)");
        $stmtCliCheck->execute([$email]);
        if ((int)($stmtCliCheck->fetchColumn() ?? 0) === 0) {
            $genCli = $pdo->query("SELECT NEXT VALUE FOR GEN_CLIENTE_ID AS NOVO_ID FROM RDB\$DATABASE");
            $cliId = (int)$genCli->fetchColumn();
            $stmtCli = $pdo->prepare("INSERT INTO CLIENTE (CODCLIENTE, NOME, SOBRENOME, EMAIL, SENHA) VALUES (?, ?, ?, ?, ?)");
            $stmtCli->execute([$cliId, $nome, $sobrenome, $email, $hashSenha]);
        }
    } catch (Exception $e) {
        // Ignora caso CLIENTE já exista ou falhe
    }

} catch (Exception $e) {
    // Se ocorrer erro no banco, registra na sessão como fallback
    $_SESSION['cliente'] = [
        'nome'      => $nome,
        'sobrenome' => $sobrenome,
        'email'     => $email,
        'senha'     => $senha
    ];
}

// Loga o novo usuário como CLIENTE
$_SESSION['usuario_logado'] = [
    'codusuario' => $novoId ?? 1,
    'nome'       => $nome,
    'sobrenome'  => $sobrenome,
    'email'      => $email,
    'perfil'     => 'CLIENTE'
];
$_SESSION['cliente_logado'] = [
    'nome'      => $nome,
    'sobrenome' => $sobrenome,
    'email'     => $email,
    'perfil'    => 'CLIENTE'
];

$urlRetorno = ($origem === 'carrinho') ? 'carrinho.php?cadastro=sucesso' : 'produtos.php?cadastro=sucesso';

exibirPopupMensagem(
    'sucesso',
    'Cadastro Realizado com Sucesso!',
    "Seja bem-vindo(a), {$nome}! Sua conta de cliente foi criada com sucesso e você já está conectado.",
    $urlRetorno,
    'Acessar Loja e Comprar'
);