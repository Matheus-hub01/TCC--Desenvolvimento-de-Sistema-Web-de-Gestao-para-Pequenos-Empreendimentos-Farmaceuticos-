<?php
/**
 * Backend AJAX para Ações Administrativas e Operacionais do Painel
 * Drogaria PharmaPaz
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/conexao.php';

$usuarioLogado = $_SESSION['usuario_logado'] ?? null;
if (!$usuarioLogado) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$perfil = strtoupper(trim($usuarioLogado['perfil'] ?? 'CLIENTE'));
$isAdmin = str_contains($perfil, 'ADMIN');
$isSeparador = str_contains($perfil, 'SEPAR') || $isAdmin;

if (!$isSeparador && !$isAdmin) {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso não autorizado para seu perfil.']);
    exit;
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    switch ($acao) {
        // -------------------------------------------------------------
        // 1. Atualizar Status de Pedido (Separador e Administrador)
        // -------------------------------------------------------------
        case 'atualizar_status_pedido':
            $codPedido = (int)($_POST['codpedido'] ?? 0);
            $novoStatus = trim($_POST['status'] ?? '');
            $statusPermitidos = [
                'Aguardando preparação',
                'Em separação',
                'Pronto para retirada',
                'Entregue',
                'Cancelado'
            ];

            if ($codPedido <= 0 || !in_array($novoStatus, $statusPermitidos)) {
                echo json_encode(['sucesso' => false, 'erro' => 'Status ou pedido inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE PEDIDO SET STATUS = ? WHERE CODPEDIDO = ?");
            $stmt->execute([$novoStatus, $codPedido]);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado com sucesso!', 'novo_status' => $novoStatus]);
            exit;

        // -------------------------------------------------------------
        // 2. Salvar / Editar Usuário (Exclusivo Administrador)
        // -------------------------------------------------------------
        case 'salvar_usuario':
            if (!$isAdmin) {
                echo json_encode(['sucesso' => false, 'erro' => 'Apenas administradores podem gerenciar usuários.']);
                exit;
            }

            $codUsuario = (int)($_POST['codusuario'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $sobrenome = trim($_POST['sobrenome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $novoPerfil = strtoupper(trim($_POST['perfil'] ?? 'CLIENTE'));
            $status = trim($_POST['status'] ?? '1');
            $novaSenha = $_POST['senha'] ?? '';

            if ($nome === '' || $email === '') {
                echo json_encode(['sucesso' => false, 'erro' => 'Nome e e-mail são obrigatórios.']);
                exit;
            }

            // Normaliza perfis
            if (str_contains($novoPerfil, 'ADMIN')) {
                $novoPerfil = 'ADMINISTRADOR';
            } elseif (str_contains($novoPerfil, 'SEPAR')) {
                $novoPerfil = 'SEPARADOR';
            } else {
                $novoPerfil = 'CLIENTE';
            }

            if ($codUsuario > 0) {
                // Edição de usuário existente
                if ($novaSenha !== '') {
                    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE USUARIO
                        SET NOME = ?, SOBRENOME = ?, EMAIL = ?, PERFIL = ?, STATUS = ?, SENHA = ?
                        WHERE CODUSUARIO = ?
                    ");
                    $stmt->execute([$nome, $sobrenome, $email, $novoPerfil, $status, $hash, $codUsuario]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE USUARIO
                        SET NOME = ?, SOBRENOME = ?, EMAIL = ?, PERFIL = ?, STATUS = ?
                        WHERE CODUSUARIO = ?
                    ");
                    $stmt->execute([$nome, $sobrenome, $email, $novoPerfil, $status, $codUsuario]);
                }
                echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário atualizado com sucesso!']);
            } else {
                // Criação de novo usuário
                if ($novaSenha === '') {
                    echo json_encode(['sucesso' => false, 'erro' => 'Senha é obrigatória para novo usuário.']);
                    exit;
                }
                $novoId = 1;
                try {
                    $genStmt = $pdo->query("SELECT NEXT VALUE FOR GEN_USUARIO_ID FROM RDB\$DATABASE");
                    $novoId = (int)$genStmt->fetchColumn();
                } catch (Exception $e) {
                    $maxStmt = $pdo->query("SELECT COALESCE(MAX(CODUSUARIO), 0) + 1 FROM USUARIO");
                    $novoId = (int)$maxStmt->fetchColumn();
                }

                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO USUARIO (CODUSUARIO, NOME, SOBRENOME, EMAIL, SENHA, PERFIL, STATUS)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$novoId, $nome, $sobrenome, $email, $hash, $novoPerfil, $status]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário cadastrado com sucesso!']);
            }
            exit;

        // -------------------------------------------------------------
        // 3. Salvar / Editar Produto (Separador e Administrador)
        // -------------------------------------------------------------
        case 'salvar_produto':
            $isEdicao = !empty($_POST['is_edicao']);
            $codProduto = trim($_POST['codproduto'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $preco = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
            $codGrupo = trim($_POST['codgrupo'] ?? '001');
            $estoqueQtd = (float)($_POST['estoque'] ?? '0');
            $codigoBarra = trim($_POST['codigobarra'] ?? '');

            if ($codProduto === '' || $descricao === '') {
                echo json_encode(['sucesso' => false, 'erro' => 'Código e descrição são obrigatórios.']);
                exit;
            }

            // Normaliza código com padding se numérico
            if (ctype_digit($codProduto)) {
                $codProduto = str_pad($codProduto, 10, '0', STR_PAD_LEFT);
            }

            if ($isEdicao) {
                // 1. Atualiza PRODUTO
                $stmtP = $pdo->prepare("
                    UPDATE PRODUTO
                    SET DESCRICAOVENDA = ?, PRECOUNITARIOVENDA = ?, CODGRUPOPRODUTO = ?
                    WHERE CODPRODUTO = ?
                ");
                $stmtP->execute([mb_strimwidth($descricao, 0, 150, '', 'UTF-8'), $preco, $codGrupo, $codProduto]);

                // 2. Atualiza ESTOQUE
                $stmtE = $pdo->prepare("UPDATE ESTOQUE SET QTDEESTOQUE = ? WHERE CODPRODUTO = ?");
                $stmtE->execute([$estoqueQtd, $codProduto]);

                // 3. Atualiza Código de Barras
                if ($codigoBarra !== '') {
                    $stmtBchk = $pdo->prepare("SELECT COUNT(*) FROM PRODUTOCODIGOBARRA WHERE CODPRODUTO = ?");
                    $stmtBchk->execute([$codProduto]);
                    if ((int)$stmtBchk->fetchColumn() > 0) {
                        $stmtBup = $pdo->prepare("UPDATE PRODUTOCODIGOBARRA SET CODIGOBARRA = ? WHERE CODPRODUTO = ?");
                        $stmtBup->execute([$codigoBarra, $codProduto]);
                    } else {
                        $stmtBins = $pdo->prepare("INSERT INTO PRODUTOCODIGOBARRA (CODPRODUTO, CODIGOBARRA) VALUES (?, ?)");
                        $stmtBins->execute([$codProduto, $codigoBarra]);
                    }
                }
            } else {
                // Inserção de Novo Produto
                $stmtPchk = $pdo->prepare("SELECT COUNT(*) FROM PRODUTO WHERE CODPRODUTO = ?");
                $stmtPchk->execute([$codProduto]);
                if ((int)$stmtPchk->fetchColumn() > 0) {
                    echo json_encode(['sucesso' => false, 'erro' => 'Já existe um produto cadastrado com este código.']);
                    exit;
                }

                $stmtPins = $pdo->prepare("
                    INSERT INTO PRODUTO (CODPRODUTO, DESCRICAOVENDA, PRECOUNITARIOVENDA, STDESATIVADO, CODGRUPOPRODUTO)
                    VALUES (?, ?, ?, '0', ?)
                ");
                $stmtPins->execute([$codProduto, mb_strimwidth($descricao, 0, 150, '', 'UTF-8'), $preco, $codGrupo]);

                // Estoque
                $codEstoque = 1;
                try {
                    $maxEst = $pdo->query("SELECT COALESCE(MAX(CODESTOQUE), 0) + 1 FROM ESTOQUE");
                    $codEstoque = (int)$maxEst->fetchColumn();
                } catch (Exception $e) {}

                $stmtEins = $pdo->prepare("INSERT INTO ESTOQUE (CODESTOQUE, CODPRODUTO, CODORGANIZACAO, QTDEESTOQUE) VALUES (?, ?, '001', ?)");
                $stmtEins->execute([$codEstoque, $codProduto, $estoqueQtd]);

                // Código de barras
                if ($codigoBarra !== '') {
                    $stmtBins = $pdo->prepare("INSERT INTO PRODUTOCODIGOBARRA (CODPRODUTO, CODIGOBARRA) VALUES (?, ?)");
                    $stmtBins->execute([$codProduto, $codigoBarra]);
                }
            }

            // Upload de Foto se enviada
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $nomeArquivo = $codProduto . '.' . $ext;
                    $pastasSalvar = [
                        dirname(__DIR__) . '/imagens/produtos/',
                        __DIR__ . '/imagens/produtos/',
                        __DIR__ . '/img/produtos/'
                    ];
                    foreach ($pastasSalvar as $dir) {
                        if (!is_dir($dir)) @mkdir($dir, 0777, true);
                        @copy($_FILES['foto']['tmp_name'], $dir . $nomeArquivo);
                    }
                }
            }

            echo json_encode(['sucesso' => true, 'mensagem' => 'Produto salvo com sucesso!']);
            exit;

        // -------------------------------------------------------------
        // 4. Salvar Configurações da Farmácia (Exclusivo Administrador)
        // -------------------------------------------------------------
        case 'salvar_configuracoes':
            if (!$isAdmin) {
                echo json_encode(['sucesso' => false, 'erro' => 'Apenas administradores podem alterar configurações do sistema.']);
                exit;
            }

            $nomeFarmacia = trim($_POST['nome_farmacia'] ?? 'Drogaria PharmaPaz');
            $corPrimaria = trim($_POST['cor_primaria'] ?? '#008a73');
            $corSecundaria = trim($_POST['cor_secundaria'] ?? '#009f9a');
            $corFundo = trim($_POST['cor_fundo'] ?? '#f4fbf9');

            $logoUrl = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                    $nomeLogo = 'logo-custom.' . $ext;
                    $dirImg = dirname(__DIR__) . '/imagens/';
                    if (is_dir($dirImg)) {
                        move_uploaded_file($_FILES['logo']['tmp_name'], $dirImg . $nomeLogo);
                        // Copia para codigo-fonte/imagens
                        @copy($dirImg . $nomeLogo, __DIR__ . '/imagens/' . $nomeLogo);
                        $logoUrl = 'imagens/' . $nomeLogo;
                    }
                }
            }

            if ($logoUrl) {
                $stmt = $pdo->prepare("
                    UPDATE CONFIGURACAO_SISTEMA
                    SET NOME_FARMACIA = ?, LOGO_URL = ?, COR_PRIMARIA = ?, COR_SECUNDARIA = ?, COR_FUNDO = ?
                    WHERE ID = 1
                ");
                $stmt->execute([$nomeFarmacia, $logoUrl, $corPrimaria, $corSecundaria, $corFundo]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE CONFIGURACAO_SISTEMA
                    SET NOME_FARMACIA = ?, COR_PRIMARIA = ?, COR_SECUNDARIA = ?, COR_FUNDO = ?
                    WHERE ID = 1
                ");
                $stmt->execute([$nomeFarmacia, $corPrimaria, $corSecundaria, $corFundo]);
            }

            echo json_encode(['sucesso' => true, 'mensagem' => 'Configurações salvas com sucesso!']);
            exit;

        default:
            echo json_encode(['sucesso' => false, 'erro' => 'Ação não reconhecida.']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    exit;
}

