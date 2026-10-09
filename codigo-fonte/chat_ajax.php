<?php
/**
 * Backend AJAX do Chat Interno da Drogaria PharmaPaz
 * Suporta atendimento ao vivo entre clientes e operadores (separadores e administradores)
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/conexao.php';

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// Função auxiliar para obter próximo ID de sequence ou max
function obterProximoId(PDO $pdo, string $generator, string $tabela, string $colunaId): int
{
    try {
        $st = $pdo->query("SELECT NEXT VALUE FOR {$generator} AS NOVO_ID FROM RDB\$DATABASE");
        return (int)$st->fetchColumn();
    } catch (Exception $e) {
        $st = $pdo->query("SELECT COALESCE(MAX({$colunaId}), 0) + 1 AS NOVO_ID FROM {$tabela}");
        return (int)$st->fetchColumn();
    }
}

try {
    switch ($acao) {
        // -----------------------------------------------------------------
        // 1. Cliente: Envia mensagem pelo widget da loja
        // -----------------------------------------------------------------
        case 'enviar_mensagem_cliente':
            $texto = trim($_POST['mensagem'] ?? '');
            if ($texto === '') {
                echo json_encode(['sucesso' => false, 'erro' => 'Mensagem vazia']);
                exit;
            }

            $clienteLogado = $_SESSION['cliente_logado'] ?? null;
            $nomeCliente = $clienteLogado['nome'] ?? ($_SESSION['chat_anonimo_nome'] ?? 'Cliente Visitante');
            $emailCliente = $clienteLogado['email'] ?? ($_SESSION['chat_anonimo_email'] ?? 'visitante@pharmapaz.local');
            $codCliente = isset($clienteLogado['codusuario']) ? (int)$clienteLogado['codusuario'] : null;

            // Busca conversa ativa ou cria uma nova
            $codConversa = $_SESSION['chat_codconversa'] ?? null;
            if (!$codConversa) {
                $codConversa = obterProximoId($pdo, 'GEN_CHAT_CONVERSA_ID', 'CHAT_CONVERSA', 'CODCONVERSA');
                $stmtC = $pdo->prepare("
                    INSERT INTO CHAT_CONVERSA (CODCONVERSA, CODCLIENTE, NOMECLIENTE, EMAILCLIENTE, STATUS)
                    VALUES (?, ?, ?, ?, 'ABERTA')
                ");
                $stmtC->execute([$codConversa, $codCliente, $nomeCliente, $emailCliente]);
                $_SESSION['chat_codconversa'] = $codConversa;
            } else {
                // Atualiza timestamp da conversa
                $stmtU = $pdo->prepare("UPDATE CHAT_CONVERSA SET DATAULTIMA = CURRENT_TIMESTAMP, STATUS = 'ABERTA' WHERE CODCONVERSA = ?");
                $stmtU->execute([$codConversa]);
            }

            // Insere mensagem
            $codMsg = obterProximoId($pdo, 'GEN_CHAT_MENSAGEM_ID', 'CHAT_MENSAGEM', 'CODMENSAGEM');
            $stmtM = $pdo->prepare("
                INSERT INTO CHAT_MENSAGEM (CODMENSAGEM, CODCONVERSA, REMETENTE_TIPO, REMETENTE_NOME, MENSAGEM)
                VALUES (?, ?, 'CLIENTE', ?, ?)
            ");
            $stmtM->execute([$codMsg, $codConversa, $nomeCliente, mb_strimwidth($texto, 0, 950, '', 'UTF-8')]);

            echo json_encode([
                'sucesso'     => true,
                'codconversa' => $codConversa,
                'mensagem'    => [
                    'id'        => $codMsg,
                    'remetente' => 'CLIENTE',
                    'nome'      => $nomeCliente,
                    'texto'     => htmlspecialchars($texto),
                    'hora'      => date('H:i')
                ]
            ]);
            exit;

        // -----------------------------------------------------------------
        // 2. Cliente: Obtém mensagens da conversa ativa
        // -----------------------------------------------------------------
        case 'obter_mensagens_cliente':
            $codConversa = $_SESSION['chat_codconversa'] ?? null;
            if (!$codConversa) {
                echo json_encode(['sucesso' => true, 'mensagens' => []]);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT CODMENSAGEM, REMETENTE_TIPO, REMETENTE_NOME, MENSAGEM, DATAMENSAGEM
                FROM CHAT_MENSAGEM
                WHERE CODCONVERSA = ?
                ORDER BY CODMENSAGEM ASC
            ");
            $stmt->execute([$codConversa]);
            $lista = [];
            while ($r = $stmt->fetch()) {
                $lista[] = [
                    'id'        => (int)$r['CODMENSAGEM'],
                    'remetente' => trim($r['REMETENTE_TIPO']),
                    'nome'      => trim($r['REMETENTE_NOME']),
                    'texto'     => trim($r['MENSAGEM']),
                    'hora'      => date('H:i', strtotime($r['DATAMENSAGEM']))
                ];
            }

            echo json_encode(['sucesso' => true, 'mensagens' => $lista, 'codconversa' => $codConversa]);
            exit;

        // -----------------------------------------------------------------
        // 3. Operador (Separador/Admin): Lista conversas para o popup de atendimento
        // -----------------------------------------------------------------
        case 'listar_conversas_operador':
            $statusFiltro = trim($_GET['status'] ?? 'ABERTA');
            
            $sql = "
                SELECT C.CODCONVERSA, C.NOMECLIENTE, C.EMAILCLIENTE, C.STATUS, C.DATAULTIMA,
                       (SELECT FIRST 1 M.MENSAGEM FROM CHAT_MENSAGEM M WHERE M.CODCONVERSA = C.CODCONVERSA ORDER BY M.CODMENSAGEM DESC) AS ULTIMA_MSG
                FROM CHAT_CONVERSA C
            ";
            if ($statusFiltro !== 'TODAS') {
                $sql .= " WHERE C.STATUS = ? ";
            }
            $sql .= " ORDER BY C.DATAULTIMA DESC ";

            $stmt = $pdo->prepare($sql);
            if ($statusFiltro !== 'TODAS') {
                $stmt->execute([$statusFiltro]);
            } else {
                $stmt->execute();
            }

            $conversas = [];
            while ($c = $stmt->fetch()) {
                $conversas[] = [
                    'codconversa'  => (int)$c['CODCONVERSA'],
                    'nome_cliente' => trim($c['NOMECLIENTE']),
                    'email'        => trim($c['EMAILCLIENTE']),
                    'status'       => trim($c['STATUS']),
                    'ultima_msg'   => trim($c['ULTIMA_MSG'] ?? 'Sem mensagens'),
                    'hora'         => date('d/m H:i', strtotime($c['DATAULTIMA']))
                ];
            }

            echo json_encode(['sucesso' => true, 'conversas' => $conversas]);
            exit;

        // -----------------------------------------------------------------
        // 4. Operador: Busca mensagens de uma conversa específica
        // -----------------------------------------------------------------
        case 'obter_mensagens_operador':
            $codConversa = (int)($_GET['codconversa'] ?? $_POST['codconversa'] ?? 0);
            if ($codConversa <= 0) {
                echo json_encode(['sucesso' => false, 'erro' => 'Conversa inválida']);
                exit;
            }

            $stmtConv = $pdo->prepare("SELECT NOMECLIENTE, EMAILCLIENTE, STATUS FROM CHAT_CONVERSA WHERE CODCONVERSA = ?");
            $stmtConv->execute([$codConversa]);
            $conv = $stmtConv->fetch();

            $stmt = $pdo->prepare("
                SELECT CODMENSAGEM, REMETENTE_TIPO, REMETENTE_NOME, MENSAGEM, DATAMENSAGEM
                FROM CHAT_MENSAGEM
                WHERE CODCONVERSA = ?
                ORDER BY CODMENSAGEM ASC
            ");
            $stmt->execute([$codConversa]);

            $mensagens = [];
            while ($r = $stmt->fetch()) {
                $mensagens[] = [
                    'id'        => (int)$r['CODMENSAGEM'],
                    'remetente' => trim($r['REMETENTE_TIPO']),
                    'nome'      => trim($r['REMETENTE_NOME']),
                    'texto'     => trim($r['MENSAGEM']),
                    'hora'      => date('H:i', strtotime($r['DATAMENSAGEM']))
                ];
            }

            echo json_encode([
                'sucesso'     => true,
                'nomeCliente' => trim($conv['NOMECLIENTE'] ?? 'Cliente'),
                'email'       => trim($conv['EMAILCLIENTE'] ?? ''),
                'status'      => trim($conv['STATUS'] ?? 'ABERTA'),
                'mensagens'   => $mensagens
            ]);
            exit;

        // -----------------------------------------------------------------
        // 5. Operador: Envia resposta ao cliente
        // -----------------------------------------------------------------
        case 'responder_operador':
            $codConversa = (int)($_POST['codconversa'] ?? 0);
            $texto = trim($_POST['mensagem'] ?? '');
            if ($codConversa <= 0 || $texto === '') {
                echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
                exit;
            }

            $operador = $_SESSION['usuario_logado'] ?? null;
            $nomeOperador = $operador['nome'] ?? 'Atendente PharmaPaz';
            $perfilOperador = $operador['perfil'] ?? 'OPERADOR';

            $codMsg = obterProximoId($pdo, 'GEN_CHAT_MENSAGEM_ID', 'CHAT_MENSAGEM', 'CODMENSAGEM');
            $stmtM = $pdo->prepare("
                INSERT INTO CHAT_MENSAGEM (CODMENSAGEM, CODCONVERSA, REMETENTE_TIPO, REMETENTE_NOME, MENSAGEM)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtM->execute([$codMsg, $codConversa, $perfilOperador, $nomeOperador, mb_strimwidth($texto, 0, 950, '', 'UTF-8')]);

            // Atualiza data da conversa
            $stmtU = $pdo->prepare("UPDATE CHAT_CONVERSA SET DATAULTIMA = CURRENT_TIMESTAMP WHERE CODCONVERSA = ?");
            $stmtU->execute([$codConversa]);

            echo json_encode([
                'sucesso'  => true,
                'mensagem' => [
                    'id'        => $codMsg,
                    'remetente' => $perfilOperador,
                    'nome'      => $nomeOperador,
                    'texto'     => htmlspecialchars($texto),
                    'hora'      => date('H:i')
                ]
            ]);
            exit;

        // -----------------------------------------------------------------
        // 6. Operador: Encerrar conversa
        // -----------------------------------------------------------------
        case 'encerrar_conversa':
            $codConversa = (int)($_POST['codconversa'] ?? 0);
            if ($codConversa > 0) {
                $stmt = $pdo->prepare("UPDATE CHAT_CONVERSA SET STATUS = 'ENCERRADA', DATAULTIMA = CURRENT_TIMESTAMP WHERE CODCONVERSA = ?");
                $stmt->execute([$codConversa]);
            }
            echo json_encode(['sucesso' => true]);
            exit;

        default:
            echo json_encode(['sucesso' => false, 'erro' => 'Ação não reconhecida']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    exit;
}

