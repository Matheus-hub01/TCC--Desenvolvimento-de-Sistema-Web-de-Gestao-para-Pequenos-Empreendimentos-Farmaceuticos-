<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Helper para exibir popup bonito e amigável com redirecionamento automático
function exibirPopupMensagem(string $tipo, string $titulo, string $mensagem, string $urlDestino = 'produtos.php', string $textoBotao = 'Voltar à Página Inicial', ?string $urlSecundaria = null, ?string $textoSecundario = null)
{
    $corTema = '#008a73';
    $icone = match ($tipo) {
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

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$origem = trim($_POST['origem'] ?? '');

// Se acessou login_usuario.php diretamente via GET ou sem preencher dados
if ($metodo !== 'POST' || ($email === '' && $senha === '')) {
    exibirPopupMensagem(
        'aviso',
        'Nenhum Usuário Conectado',
        'Nenhum usuário cadastrado foi identificado nesta sessão. Por favor, crie seu cadastro ou faça login para continuar.',
        'produtos.php',
        'Voltar à Loja',
        'produtos.php?abrir=cadastro',
        'Criar Meu Cadastro'
    );
}

if ($email === '' || $senha === '') {
    exibirPopupMensagem(
        'alerta',
        'Dados Incompletos',
        'Por favor, preencha o e-mail e a senha para efetuar o login.',
        'produtos.php?abrir=login',
        'Tentar Novamente',
        'produtos.php?abrir=cadastro',
        'Criar Cadastro'
    );
}

// 1. Verificação de Administradores definidos no .env
$adminUser = $env['ADMIN_USER'] ?? 'admin';
$adminPass = $env['ADMIN_PASSWORD'] ?? 'admin123';
$altAdminUser = $env['ALT_ADMIN_USER'] ?? 'edu11995295547@gmail.com';
$altAdminPass = $env['ALT_ADMIN_PASSWORD'] ?? '@Ed85962u';

$loginNormalizado = strtolower($email);
$isAdminEnv = false;
$nomeAdmin = 'Administrador';

if (
    (strtolower($adminUser) === $loginNormalizado && $senha === $adminPass) ||
    (strtolower($altAdminUser) === $loginNormalizado && $senha === $altAdminPass)
) {
    $isAdminEnv = true;
    $nomeAdmin = ($loginNormalizado === strtolower($altAdminUser)) ? 'Eduardo (Dev)' : 'Administrador';
}

if ($isAdminEnv) {
    $_SESSION['usuario_logado'] = [
        'codusuario' => 1,
        'nome'       => $nomeAdmin,
        'sobrenome'  => 'Sistema',
        'email'      => $email,
        'perfil'     => 'ADMINISTRADOR'
    ];
    $_SESSION['cliente_logado'] = [
        'nome'       => $nomeAdmin,
        'sobrenome'  => 'Sistema',
        'email'      => $email,
        'perfil'     => 'ADMINISTRADOR'
    ];

    if ($origem === 'finalizar_pedido') {
        header('Location: finalizar_pedido.php');
        exit;
    }

    header('Location: painel.php?login=sucesso');
    exit;
}

// 2. Verificação no Banco de Dados Firebird (Tabela USUARIO)
try {
    $stmt = $pdo->prepare("
        SELECT FIRST 1 CODUSUARIO, NOME, SOBRENOME, EMAIL, SENHA, PERFIL, STATUS
        FROM USUARIO
        WHERE UPPER(EMAIL) = UPPER(?) OR UPPER(NOME) = UPPER(?)
    ");
    $stmt->execute([$email, $email]);
    $usuarioDb = $stmt->fetch();

    if ($usuarioDb) {
        $senhaValida = password_verify($senha, $usuarioDb['SENHA']) || ($usuarioDb['SENHA'] === $senha);

        if (!$senhaValida) {
            exibirPopupMensagem(
                'erro',
                'Senha Incorreta',
                'A senha informada não confere com os dados cadastrados. Verifique sua digitação e tente novamente.',
                'produtos.php?abrir=login',
                'Tentar Novamente',
                'produtos.php?abrir=cadastro',
                'Criar Nova Conta'
            );
        }

        if (trim($usuarioDb['STATUS'] ?? '1') === '0') {
            exibirPopupMensagem(
                'alerta',
                'Conta Inativa',
                'Seu cadastro está atualmente inativo. Entre em contato com a farmácia para reativação do acesso.',
                'produtos.php',
                'Voltar à Página Inicial'
            );
        }

        $perfil = strtoupper(trim($usuarioDb['PERFIL'] ?? 'CLIENTE'));
        // Padronização dos nomes de perfis aceitos
        if (str_contains($perfil, 'ADMIN')) {
            $perfil = 'ADMINISTRADOR';
        } elseif (str_contains($perfil, 'SEPAR')) {
            $perfil = 'SEPARADOR';
        } else {
            $perfil = 'CLIENTE';
        }

        $_SESSION['usuario_logado'] = [
            'codusuario' => (int)$usuarioDb['CODUSUARIO'],
            'nome'       => trim($usuarioDb['NOME']),
            'sobrenome'  => trim($usuarioDb['SOBRENOME'] ?? ''),
            'email'      => trim($usuarioDb['EMAIL']),
            'perfil'     => $perfil
        ];
        $_SESSION['cliente_logado'] = [
            'nome'       => trim($usuarioDb['NOME']),
            'sobrenome'  => trim($usuarioDb['SOBRENOME'] ?? ''),
            'email'      => trim($usuarioDb['EMAIL']),
            'perfil'     => $perfil
        ];

        if ($origem === 'finalizar_pedido') {
            header('Location: finalizar_pedido.php');
            exit;
        }

        if ($perfil === 'ADMINISTRADOR' || $perfil === 'SEPARADOR') {
            header('Location: painel.php?login=sucesso');
            exit;
        }

        header('Location: produtos.php?login=sucesso');
        exit;
    }
} catch (Exception $e) {
    // Se a tabela USUARIO falhar temporariamente, segue para fallback
}

// 3. Fallback de sessão temporária (caso exista usuário antigo em $_SESSION['cliente'])
$clienteSessao = $_SESSION['cliente'] ?? null;
if ($clienteSessao && strtolower($clienteSessao['email'] ?? '') === $loginNormalizado) {
    if (($clienteSessao['senha'] ?? '') === $senha) {
        $_SESSION['cliente_logado'] = [
            'nome'      => $clienteSessao['nome'],
            'sobrenome' => $clienteSessao['sobrenome'] ?? '',
            'email'     => $clienteSessao['email'],
            'perfil'    => 'CLIENTE'
        ];
        $_SESSION['usuario_logado'] = $_SESSION['cliente_logado'];

        if ($origem === 'finalizar_pedido') {
            header('Location: finalizar_pedido.php');
            exit;
        }

        header('Location: produtos.php?login=sucesso');
        exit;
    }
}

// 4. Usuário não cadastrado - Dispara Popup com aviso e retorno automático
exibirPopupMensagem(
    'aviso',
    'Usuário Não Cadastrado',
    'Nenhum cadastro foi localizado com o e-mail ou dados informados. Por favor, faça seu cadastro para continuar navegando e realizar seus pedidos.',
    'produtos.php',
    'Voltar à Área Inicial',
    'produtos.php?abrir=cadastro',
    'Cadastrar-se Agora'
);