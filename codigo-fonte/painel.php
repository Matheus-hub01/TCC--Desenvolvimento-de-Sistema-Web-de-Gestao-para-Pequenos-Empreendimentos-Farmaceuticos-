<?php
session_start();
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/configuracao_helper.php';
require_once __DIR__ . '/funcoes_produtos.php';

$usuarioLogado = $_SESSION['usuario_logado'] ?? null;

// Validação de acesso ao Painel (Apenas Administradores e Separadores)
if (!$usuarioLogado) {
    header('Location: login_usuario.php');
    exit;
}

$perfil = strtoupper(trim($usuarioLogado['perfil'] ?? 'CLIENTE'));
$isAdmin = str_contains($perfil, 'ADMIN');
$isSeparador = str_contains($perfil, 'SEPAR') || $isAdmin;

if (!$isSeparador && !$isAdmin) {
    header('Location: produtos.php?aviso=acesso_negado');
    exit;
}

$config = obterConfiguracaoSistema($pdo);
$abaAtiva = $_GET['aba'] ?? ($isAdmin ? 'pedidos' : 'pedidos');

// 1. Dados para a aba Usuários (Somente Admin)
$usuariosClientes = [];
$usuariosSeparadores = [];
$usuariosAdmins = [];

if ($isAdmin) {
    try {
        $stmtUsers = $pdo->query("SELECT CODUSUARIO, NOME, SOBRENOME, EMAIL, PERFIL, STATUS, DATACADASTRO FROM USUARIO ORDER BY CODUSUARIO DESC");
        while ($u = $stmtUsers->fetch()) {
            $p = strtoupper(trim($u['PERFIL'] ?? 'CLIENTE'));
            if (str_contains($p, 'ADMIN')) {
                $usuariosAdmins[] = $u;
            } elseif (str_contains($p, 'SEPAR')) {
                $usuariosSeparadores[] = $u;
            } else {
                $usuariosClientes[] = $u;
            }
        }
    } catch (Exception $e) {}
}

// 2. Dados para a aba Separação de Pedidos
$pedidos = [];
try {
    $stmtPed = $pdo->query("
        SELECT CODPEDIDO, CODCLIENTE, NOMECLIENTE, DATAPEDIDO, STATUS, VALORTOTAL
        FROM PEDIDO
        ORDER BY CODPEDIDO DESC
    ");
    $pedidos = $stmtPed->fetchAll();
} catch (Exception $e) {}

// 3. Dados para a aba Produtos (com busca e paginação simples)
$buscaProd = trim($_GET['busca_prod'] ?? '');
$paginaProd = max(1, (int)($_GET['p'] ?? 1));
$itensPorPagina = 25;
$offset = ($paginaProd - 1) * $itensPorPagina;

$totalProds = 0;
$produtos = [];

try {
    $whereProd = "WHERE 1=1";
    $paramsProd = [];
    if ($buscaProd !== '') {
        $whereProd .= " AND (UPPER(P.DESCRICAOVENDA) LIKE UPPER(?) OR P.CODPRODUTO = ?)";
        $paramsProd = ['%' . $buscaProd . '%', $buscaProd];
    }

    $stmtTot = $pdo->prepare("SELECT COUNT(*) AS TOT FROM PRODUTO P {$whereProd}");
    $stmtTot->execute($paramsProd);
    $totalProds = (int)$stmtTot->fetchColumn();

    $sqlP = "
        SELECT FIRST {$itensPorPagina} SKIP {$offset}
            P.CODPRODUTO, P.DESCRICAOVENDA, P.PRECOUNITARIOVENDA, P.CODGRUPOPRODUTO,
            COALESCE(E.QTDEESTOQUE, 0) AS QTDEESTOQUE,
            (SELECT FIRST 1 CB.CODIGOBARRA FROM PRODUTOCODIGOBARRA CB WHERE CB.CODPRODUTO = P.CODPRODUTO) AS CODIGOBARRA,
            G.DESCRICAO AS NOMEGRUPO
        FROM PRODUTO P
        LEFT JOIN ESTOQUE E ON E.CODPRODUTO = P.CODPRODUTO
        LEFT JOIN GRUPOPRODUTO G ON G.CODGRUPOPRODUTO = P.CODGRUPOPRODUTO
        {$whereProd}
        ORDER BY P.CODPRODUTO ASC
    ";
    $stmtP = $pdo->prepare($sqlP);
    $stmtP->execute($paramsProd);
    $produtos = $stmtP->fetchAll();
} catch (Exception $e) {}

// Categorias para modal de produtos
$gruposProdutos = [];
try {
    $gruposProdutos = $pdo->query("SELECT CODGRUPOPRODUTO, DESCRICAO FROM GRUPOPRODUTO ORDER BY DESCRICAO")->fetchAll();
} catch (Exception $e) {}

// 4. Dados para Log de Conversas (Admin)
$logsChat = [];
if ($isAdmin) {
    try {
        $stmtChat = $pdo->query("
            SELECT C.CODCONVERSA, C.NOMECLIENTE, C.EMAILCLIENTE, C.STATUS, C.DATAINICIO, C.DATAULTIMA,
                   (SELECT COUNT(*) FROM CHAT_MENSAGEM M WHERE M.CODCONVERSA = C.CODCONVERSA) AS TOTAL_MSGS
            FROM CHAT_CONVERSA C
            ORDER BY C.CODCONVERSA DESC
        ");
        $logsChat = $stmtChat->fetchAll();
    } catch (Exception $e) {}
}

function escapeHtml($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Gestão - <?= escapeHtml($config['NOME_FARMACIA']) ?></title>
    <style>
        :root {
            --cor-primaria: <?= $config['COR_PRIMARIA'] ?>;
            --cor-secundaria: <?= $config['COR_SECUNDARIA'] ?>;
            --cor-fundo: <?= $config['COR_FUNDO'] ?>;
            --texto-principal: #0f172a;
            --texto-secundario: #475569;
            --borda: #e2e8f0;
            --branco: #ffffff;
            --cinza-bg: #f8fafc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--cor-fundo);
            color: var(--texto-principal);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header.painel-topbar {
            background: #ffffff;
            border-bottom: 1px solid var(--borda);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: var(--texto-principal);
        }
        .topbar-brand img {
            max-height: 38px;
            object-fit: contain;
        }
        .topbar-brand span {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--cor-primaria);
        }
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .badge-perfil {
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-admin { background: #fee2e2; color: #991b1b; }
        .badge-separador { background: #e0e7ff; color: #3730a3; }
        .btn-link {
            text-decoration: none;
            color: var(--cor-primaria);
            font-weight: 600;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .btn-link:hover { text-decoration: underline; }

        /* Container Principal */
        .painel-container {
            display: flex;
            flex: 1;
            max-width: 1500px;
            width: 100%;
            margin: 0 auto;
            padding: 1.5rem;
            gap: 1.5rem;
        }

        /* Sidebar de Navegação */
        aside.painel-nav {
            width: 250px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--borda);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            height: fit-content;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--texto-secundario);
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }
        .nav-item:hover {
            background: var(--cinza-bg);
            color: var(--texto-principal);
        }
        .nav-item.ativo {
            background: var(--cor-primaria);
            color: #ffffff;
        }
        .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }

        /* Área de Conteúdo */
        main.painel-conteudo {
            flex: 1;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--borda);
            padding: 1.5rem;
            min-height: 600px;
        }

        .secao-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--borda);
        }
        .secao-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--texto-principal);
        }

        /* Sub-abas de Usuários */
        .sub-abas {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--borda);
            padding-bottom: 0.5rem;
        }
        .sub-aba-btn {
            background: none;
            border: none;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--texto-secundario);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .sub-aba-btn.ativo {
            background: var(--cor-primaria);
            color: #ffffff;
        }

        /* Tabelas */
        .tabela-wrapper {
            overflow-x: auto;
        }
        table.tabela-dados {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }
        table.tabela-dados th {
            background: var(--cinza-bg);
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: var(--texto-secundario);
            border-bottom: 1px solid var(--borda);
        }
        table.tabela-dados td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--borda);
            vertical-align: middle;
        }
        table.tabela-dados tr:hover {
            background: #fdfdfd;
        }

        .badge-status {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-aguardando { background: #fef3c7; color: #92400e; }
        .status-separacao  { background: #e0e7ff; color: #3730a3; }
        .status-pronto     { background: #dcfce7; color: #166534; }
        .status-entregue   { background: #bbf7d0; color: #15803d; }
        .status-cancelado  { background: #fee2e2; color: #991b1b; }

        /* Botões */
        .btn-acao {
            background: var(--cor-primaria);
            color: #ffffff;
            border: none;
            padding: 0.45rem 0.85rem;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .btn-acao:hover { background: #006d5b; }
        .btn-secundario {
            background: #f1f5f9;
            color: var(--texto-principal);
            border: 1px solid var(--borda);
        }
        .btn-secundario:hover { background: #e2e8f0; }

        /* Formulários */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--texto-secundario);
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--borda);
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--cor-primaria);
        }

        /* Modal Genérico */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(3px);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.ativo { display: flex; }
        .modal-box {
            background: #ffffff;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            padding: 1.75rem;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1.25rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }

        /* Botão Flutuante de Chat de Operador */
        .chat-floating-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: var(--cor-primaria);
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 9999px;
            border: none;
            box-shadow: 0 8px 20px rgba(0, 138, 115, 0.4);
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-weight: 700;
            cursor: pointer;
            z-index: 80;
            transition: all 0.2s;
        }
        .chat-floating-btn:hover {
            transform: scale(1.04);
            background: #006d5b;
        }
        .chat-badge-count {
            background: #ef4444;
            color: #ffffff;
            font-size: 0.72rem;
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
        }
    </style>
</head>
<body>

    <!-- Barra Superior -->
    <header class="painel-topbar">
        <a href="produtos.php" class="topbar-brand">
            <img src="<?= escapeHtml($config['LOGO_URL']) ?>" alt="Logo" onerror="this.src='imagens/logo-pharmapaz.png'">
            <span><?= escapeHtml($config['NOME_FARMACIA']) ?> &bull; Painel</span>
        </a>

        <div class="topbar-user">
            <span class="badge-perfil <?= $isAdmin ? 'badge-admin' : 'badge-separador' ?>">
                <?= $isAdmin ? 'Administrador' : 'Separador' ?>
            </span>
            <span>Olá, <strong><?= escapeHtml($usuarioLogado['nome']) ?></strong></span>
            <a href="produtos.php" class="btn-link">Ver Loja</a>
            <a href="sair_usuario.php" class="btn-link" style="color: #ef4444;">Sair</a>
        </div>
    </header>

    <div class="painel-container">

        <!-- Navegação Lateral -->
        <aside class="painel-nav">
            <a href="?aba=pedidos" class="nav-item <?= $abaAtiva === 'pedidos' ? 'ativo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Separação de Pedidos
            </a>

            <a href="?aba=produtos" class="nav-item <?= $abaAtiva === 'produtos' ? 'ativo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Produtos do Catálogo
            </a>

            <?php if ($isAdmin): ?>
            <a href="?aba=usuarios" class="nav-item <?= $abaAtiva === 'usuarios' ? 'ativo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Usuários & Perfis
            </a>

            <a href="?aba=configuracoes" class="nav-item <?= $abaAtiva === 'configuracoes' ? 'ativo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Configurações da Loja
            </a>

            <a href="?aba=chats" class="nav-item <?= $abaAtiva === 'chats' ? 'ativo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Log de Conversas
            </a>
            <?php endif; ?>
        </aside>

        <!-- Área de Conteúdo Principal -->
        <main class="painel-conteudo">

            <!-- ======================================================== -->
            <!-- ABA 1: SEPARAÇÃO DE PEDIDOS                              -->
            <!-- ======================================================== -->
            <?php if ($abaAtiva === 'pedidos'): ?>
                <div class="secao-header">
                    <h2>Separação e Acompanhamento de Pedidos</h2>
                    <span style="font-size: 0.88rem; color: var(--texto-secundario);">Total de pedidos: <?= count($pedidos) ?></span>
                </div>

                <?php if (empty($pedidos)): ?>
                    <p style="color: var(--texto-secundario); padding: 2rem 0; text-align: center;">Nenhum pedido registrado no sistema no momento.</p>
                <?php else: ?>
                    <div class="tabela-wrapper">
                        <table class="tabela-dados">
                            <thead>
                                <tr>
                                    <th># Pedido</th>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Valor Total</th>
                                    <th>Status Atual</th>
                                    <th>Ações de Separação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $ped): 
                                    $st = trim($ped['STATUS'] ?? 'Aguardando preparação');
                                    $classeStatus = match($st) {
                                        'Em separação'        => 'status-separacao',
                                        'Pronto para retirada'=> 'status-pronto',
                                        'Entregue'            => 'status-entregue',
                                        'Cancelado'           => 'status-cancelado',
                                        default               => 'status-aguardando'
                                    };
                                ?>
                                <tr>
                                    <td><strong>#<?= (int)$ped['CODPEDIDO'] ?></strong></td>
                                    <td><?= escapeHtml($ped['NOMECLIENTE'] ?? 'Cliente') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($ped['DATAPEDIDO'] ?? 'now')) ?></td>
                                    <td><strong>R$ <?= number_format((float)$ped['VALORTOTAL'], 2, ',', '.') ?></strong></td>
                                    <td><span class="badge-status <?= $classeStatus ?>"><?= escapeHtml($st) ?></span></td>
                                    <td>
                                        <select onchange="alterarStatusPedido(<?= (int)$ped['CODPEDIDO'] ?>, this.value)" style="padding: 0.35rem; border-radius: 6px; font-size: 0.82rem;">
                                            <option value="">Alterar status...</option>
                                            <option value="Aguardando preparação" <?= $st === 'Aguardando preparação' ? 'selected' : '' ?>>Aguardando preparação</option>
                                            <option value="Em separação" <?= $st === 'Em separação' ? 'selected' : '' ?>>Em separação</option>
                                            <option value="Pronto para retirada" <?= $st === 'Pronto para retirada' ? 'selected' : '' ?>>Pronto para retirada</option>
                                            <option value="Entregue" <?= $st === 'Entregue' ? 'selected' : '' ?>>Entregue</option>
                                            <option value="Cancelado" <?= $st === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <!-- ======================================================== -->
            <!-- ABA 2: PRODUTOS DO CATÁLOGO                             -->
            <!-- ======================================================== -->
            <?php elseif ($abaAtiva === 'produtos'): ?>
                <div class="secao-header">
                    <h2>Gestão de Produtos (<?= $totalProds ?> itens cadastrados)</h2>
                    <button class="btn-acao" onclick="abrirModalProduto()">+ Cadastrar Novo Produto</button>
                </div>

                <form method="GET" style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem;">
                    <input type="hidden" name="aba" value="produtos">
                    <input type="text" name="busca_prod" value="<?= escapeHtml($buscaProd) ?>" placeholder="Buscar por código, nome ou descrição..." style="flex: 1; padding: 0.65rem 0.85rem; border: 1px solid var(--borda); border-radius: 8px;">
                    <button type="submit" class="btn-acao">Buscar</button>
                    <?php if ($buscaProd): ?>
                        <a href="?aba=produtos" class="btn-acao btn-secundario" style="text-decoration: none;">Limpar</a>
                    <?php endif; ?>
                </form>

                <div class="tabela-wrapper">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Código</th>
                                <th>Código de Barras</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $p): 
                                $imgSrc = imagemProduto($p['CODPRODUTO'], $p['CODIGOBARRA'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= escapeHtml($imgSrc) ?>" alt="Prod" style="width: 42px; height: 42px; object-fit: contain; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0;" onerror="this.src='imagens/produtos/sem-imagem.svg'">
                                </td>
                                <td><code><?= escapeHtml($p['CODPRODUTO']) ?></code></td>
                                <td><code><?= escapeHtml($p['CODIGOBARRA'] ?? '-') ?></code></td>
                                <td><strong><?= escapeHtml($p['DESCRICAOVENDA']) ?></strong></td>
                                <td><?= escapeHtml($p['NOMEGRUPO'] ?? 'Geral') ?></td>
                                <td>R$ <?= number_format((float)$p['PRECOUNITARIOVENDA'], 2, ',', '.') ?></td>
                                <td><?= (float)$p['QTDEESTOQUE'] ?> un</td>
                                <td>
                                    <button class="btn-acao btn-secundario" onclick='editarProduto(<?= json_encode($p) ?>)'>Editar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php $totalPaginas = ceil($totalProds / $itensPorPagina); ?>
                <?php if ($totalPaginas > 1): ?>
                <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.5rem;">
                    <?php if ($paginaProd > 1): ?>
                        <a href="?aba=produtos&p=<?= $paginaProd - 1 ?>&busca_prod=<?= urlencode($buscaProd) ?>" class="btn-acao btn-secundario">&laquo; Anterior</a>
                    <?php endif; ?>
                    <span style="display: inline-flex; align-items: center; font-size: 0.88rem; color: var(--texto-secundario);">Página <?= $paginaProd ?> de <?= $totalPaginas ?></span>
                    <?php if ($paginaProd < $totalPaginas): ?>
                        <a href="?aba=produtos&p=<?= $paginaProd + 1 ?>&busca_prod=<?= urlencode($buscaProd) ?>" class="btn-acao btn-secundario">Próxima &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <!-- ======================================================== -->
            <!-- ABA 3: USUÁRIOS & PERFIS (ADMINISTRADOR)                 -->
            <!-- ======================================================== -->
            <?php elseif ($abaAtiva === 'usuarios' && $isAdmin): ?>
                <div class="secao-header">
                    <h2>Gerenciamento de Perfis de Usuários</h2>
                    <button class="btn-acao" onclick="abrirModalUsuarioNovo()">+ Novo Usuário</button>
                </div>

                <!-- Sub-abas -->
                <div class="sub-abas">
                    <button class="sub-aba-btn ativo" onclick="alternarSubAba('clientes', this)">Clientes (<?= count($usuariosClientes) ?>)</button>
                    <button class="sub-aba-btn" onclick="alternarSubAba('separadores', this)">Separadores (<?= count($usuariosSeparadores) ?>)</button>
                    <button class="sub-aba-btn" onclick="alternarSubAba('admins', this)">Administradores (<?= count($usuariosAdmins) ?>)</button>
                </div>

                <!-- Conteúdo Clientes -->
                <div id="subAba-clientes" class="conteudo-sub-aba">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Completo</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosClientes as $uc): ?>
                            <tr>
                                <td>#<?= $uc['CODUSUARIO'] ?></td>
                                <td><strong><?= escapeHtml($uc['NOME'] . ' ' . ($uc['SOBRENOME'] ?? '')) ?></strong></td>
                                <td><?= escapeHtml($uc['EMAIL']) ?></td>
                                <td><span class="badge-perfil" style="background: #e2e8f0;">Cliente</span></td>
                                <td><?= trim($uc['STATUS'] ?? '1') === '1' ? '<span style="color:#16a34a; font-weight:700;">Ativo</span>' : '<span style="color:#dc2626;">Inativo</span>' ?></td>
                                <td><button class="btn-acao btn-secundario" onclick='editarUsuario(<?= json_encode($uc) ?>)'>Editar Perfil</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Conteúdo Separadores -->
                <div id="subAba-separadores" class="conteudo-sub-aba" style="display: none;">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Completo</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosSeparadores as $us): ?>
                            <tr>
                                <td>#<?= $us['CODUSUARIO'] ?></td>
                                <td><strong><?= escapeHtml($us['NOME'] . ' ' . ($us['SOBRENOME'] ?? '')) ?></strong></td>
                                <td><?= escapeHtml($us['EMAIL']) ?></td>
                                <td><span class="badge-perfil badge-separador">Separador</span></td>
                                <td><?= trim($us['STATUS'] ?? '1') === '1' ? '<span style="color:#16a34a; font-weight:700;">Ativo</span>' : '<span style="color:#dc2626;">Inativo</span>' ?></td>
                                <td><button class="btn-acao btn-secundario" onclick='editarUsuario(<?= json_encode($us) ?>)'>Editar Perfil</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Conteúdo Administradores -->
                <div id="subAba-admins" class="conteudo-sub-aba" style="display: none;">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Completo</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosAdmins as $ua): ?>
                            <tr>
                                <td>#<?= $ua['CODUSUARIO'] ?></td>
                                <td><strong><?= escapeHtml($ua['NOME'] . ' ' . ($ua['SOBRENOME'] ?? '')) ?></strong></td>
                                <td><?= escapeHtml($ua['EMAIL']) ?></td>
                                <td><span class="badge-perfil badge-admin">Administrador</span></td>
                                <td><?= trim($ua['STATUS'] ?? '1') === '1' ? '<span style="color:#16a34a; font-weight:700;">Ativo</span>' : '<span style="color:#dc2626;">Inativo</span>' ?></td>
                                <td><button class="btn-acao btn-secundario" onclick='editarUsuario(<?= json_encode($ua) ?>)'>Editar Perfil</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <!-- ======================================================== -->
            <!-- ABA 4: CONFIGURAÇÕES DA LOJA (ADMINISTRADOR)            -->
            <!-- ======================================================== -->
            <?php elseif ($abaAtiva === 'configuracoes' && $isAdmin): ?>
                <div class="secao-header">
                    <h2>Personalização e Configurações do Sistema</h2>
                </div>

                <form onsubmit="salvarConfiguracoes(event)" enctype="multipart/form-data" style="max-width: 700px;">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Nome de Exibição da Farmácia no Site *</label>
                            <input type="text" id="conf_nome" name="nome_farmacia" value="<?= escapeHtml($config['NOME_FARMACIA']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Cor Primária da Loja</label>
                            <input type="color" id="conf_cor1" name="cor_primaria" value="<?= escapeHtml($config['COR_PRIMARIA']) ?>" style="height: 44px; cursor: pointer;">
                        </div>

                        <div class="form-group">
                            <label>Cor Secundária da Loja</label>
                            <input type="color" id="conf_cor2" name="cor_secundaria" value="<?= escapeHtml($config['COR_SECUNDARIA']) ?>" style="height: 44px; cursor: pointer;">
                        </div>

                        <div class="form-group">
                            <label>Cor de Fundo da Loja</label>
                            <input type="color" id="conf_cor_fundo" name="cor_fundo" value="<?= escapeHtml($config['COR_FUNDO']) ?>" style="height: 44px; cursor: pointer;">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Logotipo do Site (PNG, SVG, WEBP)</label>
                            <div style="display: flex; align-items: center; gap: 1.5rem; margin-top: 0.5rem;">
                                <img src="<?= escapeHtml($config['LOGO_URL']) ?>" alt="Logo Atual" style="max-height: 50px; object-fit: contain; background: #f8fafc; padding: 4px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                <input type="file" name="logo" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-acao" style="padding: 0.75rem 1.5rem; font-size: 0.95rem;">Salvar Configurações</button>
                </form>

            <!-- ======================================================== -->
            <!-- ABA 5: LOG DE CONVERSAS DE ATENDIMENTO (ADMIN)           -->
            <!-- ======================================================== -->
            <?php elseif ($abaAtiva === 'chats' && $isAdmin): ?>
                <div class="secao-header">
                    <h2>Histórico & Logs de Atendimento dos Clientes</h2>
                </div>

                <?php if (empty($logsChat)): ?>
                    <p style="color: var(--texto-secundario); padding: 2rem 0; text-align: center;">Nenhum atendimento ou conversa registrado ainda.</p>
                <?php else: ?>
                    <div class="tabela-wrapper">
                        <table class="tabela-dados">
                            <thead>
                                <tr>
                                    <th># ID</th>
                                    <th>Cliente</th>
                                    <th>E-mail</th>
                                    <th>Total Msgs</th>
                                    <th>Status</th>
                                    <th>Última Atividade</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logsChat as $ch): ?>
                                <tr>
                                    <td>#<?= $ch['CODCONVERSA'] ?></td>
                                    <td><strong><?= escapeHtml($ch['NOMECLIENTE']) ?></strong></td>
                                    <td><?= escapeHtml($ch['EMAILCLIENTE'] ?? '-') ?></td>
                                    <td><?= (int)$ch['TOTAL_MSGS'] ?> mensagens</td>
                                    <td><span class="badge-status status-<?= strtolower($ch['STATUS']) === 'aberta' ? 'separacao' : 'pronto' ?>"><?= escapeHtml($ch['STATUS']) ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($ch['DATAULTIMA'])) ?></td>
                                    <td>
                                        <button class="btn-acao btn-secundario" onclick="abrirConversaOperador(<?= $ch['CODCONVERSA'] ?>)">Ver Conversa</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </main>
    </div>

    <!-- BOTÃO FLUTUANTE DE ATENDIMENTO AO VIVO (CHAT INTERNO) -->
    <button class="chat-floating-btn" onclick="abrirModalListaChats()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Atendimento ao Vivo
        <span class="chat-badge-count" id="chatBadgeAbertas" style="display: none;">0</span>
    </button>

    <!-- MODAL: GESTÃO DE USUÁRIO (ADMIN) -->
    <div class="modal-overlay" id="modalUsuario">
        <div class="modal-box">
            <button class="modal-close" onclick="fecharModal('modalUsuario')">&times;</button>
            <h3 id="modalUsuarioTitulo" style="margin-bottom: 1.25rem;">Editar Usuário</h3>
            <form onsubmit="salvarUsuario(event)">
                <input type="hidden" id="usr_codusuario" name="codusuario" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" id="usr_nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>Sobrenome</label>
                        <input type="text" id="usr_sobrenome" name="sobrenome">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>E-mail *</label>
                        <input type="email" id="usr_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Perfil de Acesso *</label>
                        <select id="usr_perfil" name="perfil" required>
                            <option value="CLIENTE">usuario-cliente (Cliente Padrão)</option>
                            <option value="SEPARADOR">usuario-separador (Separador de Pedidos & Estoque)</option>
                            <option value="ADMINISTRADOR">usuario-administrador (Acesso Total)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="usr_status" name="status">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Nova Senha (deixe em branco para não alterar)</label>
                        <input type="password" id="usr_senha" name="senha" placeholder="Digite uma nova senha se desejar">
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" class="btn-acao btn-secundario" onclick="fecharModal('modalUsuario')">Cancelar</button>
                    <button type="submit" class="btn-acao">Salvar Usuário</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: GESTÃO DE PRODUTO (NOVO/EDITAR) -->
    <div class="modal-overlay" id="modalProduto">
        <div class="modal-box">
            <button class="modal-close" onclick="fecharModal('modalProduto')">&times;</button>
            <h3 id="modalProdutoTitulo" style="margin-bottom: 1.25rem;">Cadastrar Produto</h3>
            <form onsubmit="salvarProduto(event)" enctype="multipart/form-data">
                <input type="hidden" id="prod_is_edicao" name="is_edicao" value="0">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Código Interno *</label>
                        <input type="text" id="prod_codigo" name="codproduto" required>
                    </div>
                    <div class="form-group">
                        <label>Código de Barras (EAN)</label>
                        <input type="text" id="prod_barra" name="codigobarra" placeholder="Ex: 7891010087722">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Descrição do Produto *</label>
                        <input type="text" id="prod_descricao" name="descricao" required>
                    </div>
                    <div class="form-group">
                        <label>Categoria</label>
                        <select id="prod_grupo" name="codgrupo">
                            <?php foreach ($gruposProdutos as $gp): ?>
                                <option value="<?= $gp['CODGRUPOPRODUTO'] ?>"><?= escapeHtml($gp['DESCRICAO']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Preço Unitário (R$) *</label>
                        <input type="text" id="prod_preco" name="preco" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Quantidade em Estoque</label>
                        <input type="number" id="prod_estoque" name="estoque" value="100" step="1">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Foto do Produto (PNG, WEBP, JPG)</label>
                        <input type="file" id="prod_foto" name="foto" accept="image/*">
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" class="btn-acao btn-secundario" onclick="fecharModal('modalProduto')">Cancelar</button>
                    <button type="submit" class="btn-acao">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ATENDIMENTO AO VIVO (POPUP DE CHAT) -->
    <div class="modal-overlay" id="modalChatOperador">
        <div class="modal-box" style="max-width: 650px; display: flex; flex-direction: column; height: 600px;">
            <button class="modal-close" onclick="fecharModal('modalChatOperador')">&times;</button>
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--borda); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <h3 id="chatOperadorNomeCliente">Atendimento ao Cliente</h3>
                    <span id="chatOperadorEmailCliente" style="font-size: 0.8rem; color: var(--texto-secundario);"></span>
                </div>
                <button class="btn-acao btn-secundario" onclick="encerrarConversaAtual()" style="font-size: 0.75rem;">Encerrar Atendimento</button>
            </div>

            <!-- Lista de Mensagens -->
            <div id="chatOperadorMensagens" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.65rem; padding: 0.5rem; background: #f8fafc; border-radius: 8px; border: 1px solid var(--borda);">
                <!-- Mensagens carregadas via AJAX -->
            </div>

            <!-- Input de envio -->
            <form onsubmit="enviarMensagemOperador(event)" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <input type="hidden" id="chatOperadorCodConversa" value="">
                <input type="text" id="chatOperadorInput" placeholder="Digite sua resposta para o cliente..." required style="flex: 1; padding: 0.65rem 0.85rem; border: 1px solid var(--borda); border-radius: 8px; outline: none;">
                <button type="submit" class="btn-acao">Responder</button>
            </form>
        </div>
    </div>

    <!-- MODAL: LISTAGEM DE CONVERSAS ABERTAS -->
    <div class="modal-overlay" id="modalListaChats">
        <div class="modal-box" style="max-width: 550px;">
            <button class="modal-close" onclick="fecharModal('modalListaChats')">&times;</button>
            <h3 style="margin-bottom: 1rem;">Conversas em Aberto</h3>
            <div id="listaConversasConteudo" style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 450px; overflow-y: auto;">
                <p style="color: var(--texto-secundario);">Carregando atendimentos...</p>
            </div>
        </div>
    </div>

    <!-- SCRIPTS JS DO PAINEL -->
    <script>
        function fecharModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.remove('ativo');
        }

        function alternarSubAba(tipo, btn) {
            document.querySelectorAll('.sub-aba-btn').forEach(b => b.classList.remove('ativo'));
            document.querySelectorAll('.conteudo-sub-aba').forEach(c => c.style.display = 'none');
            btn.classList.add('ativo');
            const target = document.getElementById('subAba-' + tipo);
            if (target) target.style.display = 'block';
        }

        // Alterar status de pedido
        function alterarStatusPedido(codPedido, novoStatus) {
            if (!novoStatus) return;
            const fd = new FormData();
            fd.append('acao', 'atualizar_status_pedido');
            fd.append('codpedido', codPedido);
            fd.append('status', novoStatus);

            fetch('painel_ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        alert('Status atualizado para: ' + novoStatus);
                        window.location.reload();
                    } else {
                        alert('Erro: ' + (res.erro || 'Falha ao atualizar'));
                    }
                });
        }

        // Usuários
        function abrirModalUsuarioNovo() {
            document.getElementById('modalUsuarioTitulo').textContent = 'Novo Usuário';
            document.getElementById('usr_codusuario').value = '';
            document.getElementById('usr_nome').value = '';
            document.getElementById('usr_sobrenome').value = '';
            document.getElementById('usr_email').value = '';
            document.getElementById('usr_perfil').value = 'CLIENTE';
            document.getElementById('usr_status').value = '1';
            document.getElementById('usr_senha').value = '';
            document.getElementById('usr_senha').required = true;
            document.getElementById('modalUsuario').classList.add('ativo');
        }

        function editarUsuario(u) {
            document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuário #' + u.CODUSUARIO;
            document.getElementById('usr_codusuario').value = u.CODUSUARIO;
            document.getElementById('usr_nome').value = u.NOME || '';
            document.getElementById('usr_sobrenome').value = u.SOBRENOME || '';
            document.getElementById('usr_email').value = u.EMAIL || '';
            
            let p = (u.PERFIL || 'CLIENTE').toUpperCase();
            if (p.includes('ADMIN')) p = 'ADMINISTRADOR';
            else if (p.includes('SEPAR')) p = 'SEPARADOR';
            else p = 'CLIENTE';

            document.getElementById('usr_perfil').value = p;
            document.getElementById('usr_status').value = (u.STATUS || '1').trim();
            document.getElementById('usr_senha').value = '';
            document.getElementById('usr_senha').required = false;
            document.getElementById('modalUsuario').classList.add('ativo');
        }

        function salvarUsuario(e) {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            fd.append('acao', 'salvar_usuario');

            fetch('painel_ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        alert(res.mensagem);
                        window.location.reload();
                    } else {
                        alert('Erro: ' + (res.erro || 'Falha ao salvar'));
                    }
                });
        }

        // Produtos
        function abrirModalProduto() {
            document.getElementById('modalProdutoTitulo').textContent = 'Cadastrar Novo Produto';
            document.getElementById('prod_is_edicao').value = '0';
            document.getElementById('prod_codigo').value = '';
            document.getElementById('prod_codigo').readOnly = false;
            document.getElementById('prod_barra').value = '';
            document.getElementById('prod_descricao').value = '';
            document.getElementById('prod_preco').value = '';
            document.getElementById('prod_estoque').value = '100';
            document.getElementById('prod_foto').value = '';
            document.getElementById('modalProduto').classList.add('ativo');
        }

        function editarProduto(p) {
            document.getElementById('modalProdutoTitulo').textContent = 'Editar Produto #' + p.CODPRODUTO;
            document.getElementById('prod_is_edicao').value = '1';
            document.getElementById('prod_codigo').value = p.CODPRODUTO;
            document.getElementById('prod_codigo').readOnly = true;
            document.getElementById('prod_barra').value = p.CODIGOBARRA || '';
            document.getElementById('prod_descricao').value = p.DESCRICAOVENDA || '';
            document.getElementById('prod_grupo').value = p.CODGRUPOPRODUTO || '001';
            document.getElementById('prod_preco').value = parseFloat(p.PRECOUNITARIOVENDA || 0).toFixed(2);
            document.getElementById('prod_estoque').value = p.QTDEESTOQUE || '0';
            document.getElementById('prod_foto').value = '';
            document.getElementById('modalProduto').classList.add('ativo');
        }

        function salvarProduto(e) {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            fd.append('acao', 'salvar_produto');

            fetch('painel_ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        alert(res.mensagem);
                        window.location.reload();
                    } else {
                        alert('Erro: ' + (res.erro || 'Falha ao salvar'));
                    }
                });
        }

        // Configurações
        function salvarConfiguracoes(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('acao', 'salvar_configuracoes');

            fetch('painel_ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        alert('Configurações salvas com sucesso!');
                        window.location.reload();
                    } else {
                        alert('Erro: ' + (res.erro || 'Falha'));
                    }
                });
        }

        // Live Chat Operador
        let timerPollChat = null;
        function verificarConversasAbertas() {
            fetch('chat_ajax.php?acao=listar_conversas_operador&status=ABERTA')
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso && res.conversas) {
                        const qtd = res.conversas.length;
                        const badge = document.getElementById('chatBadgeAbertas');
                        if (badge) {
                            badge.textContent = qtd;
                            badge.style.display = qtd > 0 ? 'inline-block' : 'none';
                        }
                    }
                })
                .catch(() => {});
        }
        setInterval(verificarConversasAbertas, 6000);
        verificarConversasAbertas();

        function abrirModalListaChats() {
            fetch('chat_ajax.php?acao=listar_conversas_operador&status=ABERTA')
                .then(r => r.json())
                .then(res => {
                    const c = document.getElementById('listaConversasConteudo');
                    if (!res.sucesso || !res.conversas || res.conversas.length === 0) {
                        c.innerHTML = '<p style="color: var(--texto-secundario); padding: 1rem 0;">Nenhuma conversa em aberto no momento.</p>';
                    } else {
                        c.innerHTML = res.conversas.map(conv => `
                            <div style="padding: 0.85rem; background: #f8fafc; border: 1px solid var(--borda); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>${conv.nome_cliente}</strong> <span style="font-size:0.78rem; color:#64748b;">(${conv.hora})</span>
                                    <p style="font-size: 0.82rem; color: #475569; margin-top: 0.25rem;">${conv.ultima_msg}</p>
                                </div>
                                <button class="btn-acao" onclick="abrirConversaOperador(${conv.codconversa})">Atender</button>
                            </div>
                        `).join('');
                    }
                    document.getElementById('modalListaChats').classList.add('ativo');
                });
        }

        function abrirConversaOperador(codConversa) {
            fecharModal('modalListaChats');
            document.getElementById('chatOperadorCodConversa').value = codConversa;
            carregarMensagensOperador(codConversa);
            document.getElementById('modalChatOperador').classList.add('ativo');

            if (timerPollChat) clearInterval(timerPollChat);
            timerPollChat = setInterval(() => {
                const cod = document.getElementById('chatOperadorCodConversa').value;
                if (cod) carregarMensagensOperador(cod, false);
            }, 3000);
        }

        function carregarMensagensOperador(codConversa, scroll = true) {
            fetch('chat_ajax.php?acao=obter_mensagens_operador&codconversa=' + codConversa)
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        document.getElementById('chatOperadorNomeCliente').textContent = res.nomeCliente;
                        document.getElementById('chatOperadorEmailCliente').textContent = res.email || '';
                        
                        const cont = document.getElementById('chatOperadorMensagens');
                        cont.innerHTML = res.mensagens.map(m => {
                            const isCliente = m.remetente === 'CLIENTE';
                            return `
                                <div style="align-self: ${isCliente ? 'flex-start' : 'flex-end'}; max-width: 80%; background: ${isCliente ? '#ffffff' : 'var(--cor-primaria)'}; color: ${isCliente ? '#0f172a' : '#ffffff'}; padding: 0.6rem 0.85rem; border-radius: 10px; font-size: 0.86rem; border: ${isCliente ? '1px solid #e2e8f0' : 'none'}; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                    <div style="font-size: 0.72rem; opacity: 0.8; margin-bottom: 0.2rem;">${m.nome} &bull; ${m.hora}</div>
                                    <div>${m.texto}</div>
                                </div>
                            `;
                        }).join('');

                        if (scroll) cont.scrollTop = cont.scrollHeight;
                    }
                });
        }

        function enviarMensagemOperador(e) {
            e.preventDefault();
            const input = document.getElementById('chatOperadorInput');
            const cod = document.getElementById('chatOperadorCodConversa').value;
            const texto = input.value.trim();
            if (!texto || !cod) return;

            const fd = new FormData();
            fd.append('acao', 'responder_operador');
            fd.append('codconversa', cod);
            fd.append('mensagem', texto);

            input.value = '';
            fetch('chat_ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        carregarMensagensOperador(cod);
                    }
                });
        }

        function encerrarConversaAtual() {
            const cod = document.getElementById('chatOperadorCodConversa').value;
            if (!cod || !confirm('Deseja encerrar este atendimento?')) return;
            const fd = new FormData();
            fd.append('acao', 'encerrar_conversa');
            fd.append('codconversa', cod);
            fetch('chat_ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    fecharModal('modalChatOperador');
                    verificarConversasAbertas();
                });
        }
    </script>
</body>
</html>

