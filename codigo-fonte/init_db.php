<?php
/**
 * Inicializador e Populador do Banco de Dados Firebird
 * Drogaria PharmaPaz - TCC Gestão Farmacêutica
 *
 * Executa a criação de tabelas (se não existirem), limpa produtos demo (101 a 124),
 * popula os administradores do .env, configurações do sistema, categorias
 * e produtos do catálogo CSV com sincronização de imagens.
 */

if (php_sapi_name() === 'cli') {
    echo "=== INICIALIZADOR DO BANCO DE DADOS PHARMAPAZ ===\n";
}

require_once __DIR__ . '/conexao.php';

// Caminhos base
$dirRaiz = dirname(__DIR__);
$csvFile = $dirRaiz . '/atualizacoes_eduardo/produtos_para_classificar.csv';
$dirImagensOrigem = $dirRaiz . '/imagens/produtos';
$dirImagensDestino = __DIR__ . '/imagens/produtos';
$dirImgDestino = __DIR__ . '/img/produtos';

// 1. Sincroniza imagens de produtos
foreach ([$dirImagensDestino, $dirImgDestino] as $dirDest) {
    if (!is_dir($dirDest)) {
        @mkdir($dirDest, 0777, true);
    }
}

if (is_dir($dirImagensOrigem)) {
    $arquivosImg = scandir($dirImagensOrigem);
    $copiados = 0;
    foreach ($arquivosImg as $img) {
        if ($img === '.' || $img === '..') continue;
        $origem = $dirImagensOrigem . '/' . $img;
        
        foreach ([$dirImagensDestino, $dirImgDestino] as $dirDest) {
            $destino = $dirDest . '/' . $img;
            if (!file_exists($destino) || filesize($destino) !== filesize($origem)) {
                @copy($origem, $destino);
                $copiados++;
            }
        }
    }
    if (php_sapi_name() === 'cli' && $copiados > 0) {
        echo "[IMAGENS] {$copiados} imagens sincronizadas com sucesso.\n";
    }
}

// 2. Garante que as tabelas existem no Firebird
function tabelaExiste(PDO $pdo, string $nomeTabela): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) AS QTD FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?");
    $stmt->execute([strtoupper($nomeTabela)]);
    return (int)($stmt->fetch()['QTD'] ?? 0) > 0;
}

function sequenceExiste(PDO $pdo, string $nomeSeq): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) AS QTD FROM RDB\$GENERATORS WHERE RDB\$GENERATOR_NAME = ?");
    $stmt->execute([strtoupper($nomeSeq)]);
    return (int)($stmt->fetch()['QTD'] ?? 0) > 0;
}

// DDL das tabelas
$ddl = [
    'GRUPOPRODUTO' => "
        CREATE TABLE GRUPOPRODUTO (
            CODGRUPOPRODUTO VARCHAR(10) NOT NULL PRIMARY KEY,
            DESCRICAO VARCHAR(100) NOT NULL
        )
    ",
    'PRODUTO' => "
        CREATE TABLE PRODUTO (
            CODPRODUTO VARCHAR(30) NOT NULL PRIMARY KEY,
            DESCRICAOVENDA VARCHAR(255) NOT NULL,
            PRECOUNITARIOVENDA NUMERIC(15,2) DEFAULT 0.00,
            STDESATIVADO CHAR(1) DEFAULT '0',
            CODGRUPOPRODUTO VARCHAR(10)
        )
    ",
    'ESTOQUE' => "
        CREATE TABLE ESTOQUE (
            CODESTOQUE INTEGER NOT NULL PRIMARY KEY,
            CODPRODUTO VARCHAR(30) NOT NULL,
            CODORGANIZACAO VARCHAR(10) DEFAULT '001',
            QTDEESTOQUE NUMERIC(15,3) DEFAULT 0.000
        )
    ",
    'PRODUTOCODIGOBARRA' => "
        CREATE TABLE PRODUTOCODIGOBARRA (
            CODPRODUTO VARCHAR(30) NOT NULL,
            CODIGOBARRA VARCHAR(30) NOT NULL,
            PRIMARY KEY (CODPRODUTO, CODIGOBARRA)
        )
    ",
    'CLIENTE' => "
        CREATE TABLE CLIENTE (
            CODCLIENTE INTEGER NOT NULL PRIMARY KEY,
            NOME VARCHAR(100) NOT NULL,
            SOBRENOME VARCHAR(100),
            EMAIL VARCHAR(150) NOT NULL,
            SENHA VARCHAR(255) NOT NULL,
            DATACADASTRO TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    'PEDIDO' => "
        CREATE TABLE PEDIDO (
            CODPEDIDO INTEGER NOT NULL PRIMARY KEY,
            CODCLIENTE INTEGER,
            NOMECLIENTE VARCHAR(150),
            DATAPEDIDO TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            STATUS VARCHAR(50) DEFAULT 'Aguardando preparação',
            VALORTOTAL NUMERIC(15,2) DEFAULT 0.00
        )
    ",
    'ITEMPEDIDO' => "
        CREATE TABLE ITEMPEDIDO (
            CODITEMPEDIDO INTEGER NOT NULL PRIMARY KEY,
            CODPEDIDO INTEGER NOT NULL,
            CODPRODUTO VARCHAR(30) NOT NULL,
            NOMEPRODUTO VARCHAR(255),
            QUANTIDADE NUMERIC(15,3) NOT NULL,
            PRECOUNITARIO NUMERIC(15,2) NOT NULL,
            SUBTOTAL NUMERIC(15,2) NOT NULL
        )
    ",
    'USUARIO' => "
        CREATE TABLE USUARIO (
            CODUSUARIO INTEGER NOT NULL PRIMARY KEY,
            NOME VARCHAR(100) NOT NULL,
            SOBRENOME VARCHAR(100),
            EMAIL VARCHAR(150) NOT NULL,
            SENHA VARCHAR(255) NOT NULL,
            PERFIL VARCHAR(30) DEFAULT 'CLIENTE',
            TELEFONE VARCHAR(30),
            DATACADASTRO TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            STATUS CHAR(1) DEFAULT '1'
        )
    ",
    'CONFIGURACAO_SISTEMA' => "
        CREATE TABLE CONFIGURACAO_SISTEMA (
            ID INTEGER NOT NULL PRIMARY KEY,
            NOME_FARMACIA VARCHAR(150) DEFAULT 'Drogaria PharmaPaz',
            LOGO_URL VARCHAR(255) DEFAULT 'img/logo-pharmapaz.png',
            COR_PRIMARIA VARCHAR(20) DEFAULT '#008a73',
            COR_SECUNDARIA VARCHAR(20) DEFAULT '#009f9a',
            COR_FUNDO VARCHAR(20) DEFAULT '#f4fbf9'
        )
    ",
    'CHAT_CONVERSA' => "
        CREATE TABLE CHAT_CONVERSA (
            CODCONVERSA INTEGER NOT NULL PRIMARY KEY,
            CODCLIENTE INTEGER,
            NOMECLIENTE VARCHAR(100) NOT NULL,
            EMAILCLIENTE VARCHAR(150),
            STATUS VARCHAR(30) DEFAULT 'ABERTA',
            DATAINICIO TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            DATAULTIMA TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    'CHAT_MENSAGEM' => "
        CREATE TABLE CHAT_MENSAGEM (
            CODMENSAGEM INTEGER NOT NULL PRIMARY KEY,
            CODCONVERSA INTEGER NOT NULL,
            REMETENTE_TIPO VARCHAR(30) NOT NULL,
            REMETENTE_NOME VARCHAR(100) NOT NULL,
            MENSAGEM VARCHAR(1000) NOT NULL,
            DATAMENSAGEM TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    "
];

foreach ($ddl as $tab => $sql) {
    if (!tabelaExiste($pdo, $tab)) {
        try {
            if ($pdo->inTransaction()) $pdo->commit();
            $pdo->exec($sql);
            if (php_sapi_name() === 'cli') {
                echo "[TABELA] Criada tabela: {$tab}\n";
            }
        } catch (Exception $e) {
            if (php_sapi_name() === 'cli') {
                echo "[AVISO] Criacao da tabela {$tab}: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Generators / Sequences
$generators = [
    'GEN_ESTOQUE_ID', 'GEN_CLIENTE_ID', 'GEN_PEDIDO_ID', 'GEN_ITEMPEDIDO_ID',
    'GEN_USUARIO_ID', 'GEN_CONFIG_ID', 'GEN_CHAT_CONVERSA_ID', 'GEN_CHAT_MENSAGEM_ID'
];
foreach ($generators as $gen) {
    if (!sequenceExiste($pdo, $gen)) {
        try {
            if ($pdo->inTransaction()) $pdo->commit();
            $pdo->exec("CREATE SEQUENCE {$gen}");
        } catch (Exception $e) {
            // Ignora se ja existir ou se necessitar de permissao SYSDBA
        }
    }
}

// 3. Limpeza de produtos demo/exemplo (101 a 124)
try {
    $codigosDemo = [];
    for ($i = 101; $i <= 124; $i++) {
        $codigosDemo[] = (string)$i;
    }
    $inPlaceholders = "'" . implode("','", $codigosDemo) . "'";
    $pdo->exec("DELETE FROM ESTOQUE WHERE CODPRODUTO IN ({$inPlaceholders})");
    $pdo->exec("DELETE FROM PRODUTOCODIGOBARRA WHERE CODPRODUTO IN ({$inPlaceholders})");
    $pdo->exec("DELETE FROM PRODUTO WHERE CODPRODUTO IN ({$inPlaceholders})");
} catch (Exception $e) {
    // Ignora se já estiverem limpos
}

// 4. Inserir administradores do .env se não existirem
$adminUser = $env['ADMIN_USER'] ?? 'admin';
$adminPass = $env['ADMIN_PASSWORD'] ?? 'admin123';
$altAdminUser = $env['ALT_ADMIN_USER'] ?? 'edu11995295547@gmail.com';
$altAdminPass = $env['ALT_ADMIN_PASSWORD'] ?? '@Ed85962u';

$padraoAdmins = [
    ['nome' => 'Administrador', 'email' => $adminUser, 'senha' => $adminPass, 'perfil' => 'ADMINISTRADOR'],
    ['nome' => 'Eduardo (Dev)', 'email' => $altAdminUser, 'senha' => $altAdminPass, 'perfil' => 'ADMINISTRADOR']
];

foreach ($padraoAdmins as $adm) {
    try {
        $stmtChk = $pdo->prepare("SELECT COUNT(*) AS QTD FROM USUARIO WHERE UPPER(EMAIL) = UPPER(?)");
        $stmtChk->execute([$adm['email']]);
        if ((int)($stmtChk->fetchColumn() ?? 0) === 0) {
            $novoId = 1;
            try {
                $genStmt = $pdo->query("SELECT NEXT VALUE FOR GEN_USUARIO_ID FROM RDB\$DATABASE");
                $novoId = (int)$genStmt->fetchColumn();
            } catch (Exception $e) {
                $maxStmt = $pdo->query("SELECT COALESCE(MAX(CODUSUARIO), 0) + 1 AS MAX_ID FROM USUARIO");
                $novoId = (int)$maxStmt->fetchColumn();
            }
            $hashSenha = password_hash($adm['senha'], PASSWORD_DEFAULT);
            $stmtIns = $pdo->prepare("
                INSERT INTO USUARIO (CODUSUARIO, NOME, SOBRENOME, EMAIL, SENHA, PERFIL, STATUS)
                VALUES (?, ?, 'Sistema', ?, ?, ?, '1')
            ");
            $stmtIns->execute([$novoId, $adm['nome'], $adm['email'], $hashSenha, $adm['perfil']]);
            if (php_sapi_name() === 'cli') {
                echo "[USUARIO] Administrador padrão inserido: {$adm['email']}\n";
            }
        }
    } catch (Exception $e) {
        // Ignora
    }
}

// 5. Inserir configuração do sistema se não existir
try {
    $stmtConf = $pdo->query("SELECT COUNT(*) AS QTD FROM CONFIGURACAO_SISTEMA WHERE ID = 1");
    if ((int)($stmtConf->fetchColumn() ?? 0) === 0) {
        $pdo->exec("INSERT INTO CONFIGURACAO_SISTEMA (ID, NOME_FARMACIA, LOGO_URL, COR_PRIMARIA, COR_SECUNDARIA, COR_FUNDO)
                    VALUES (1, 'Drogaria PharmaPaz', 'img/logo-pharmapaz.png', '#008a73', '#009f9a', '#f4fbf9')");
    }
} catch (Exception $e) {
    // Ignora
}

// 6. Garante categorias em GRUPOPRODUTO
$mapaCategorias = [
    'MEDICAMENTOS'        => ['cod' => '001', 'desc' => 'Medicamentos'],
    'GENERICO'            => ['cod' => '002', 'desc' => 'Medicamentos Genéricos'],
    'MEDICAMENTOS/FRACAO' => ['cod' => '003', 'desc' => 'Medicamentos Fracionados'],
    'PERFUMARIA'          => ['cod' => '004', 'desc' => 'Perfumaria & Higiene'],
    'COMESTIVEIS'         => ['cod' => '005', 'desc' => 'Alimentos & Suplementos'],
    'ACESSORIOS'          => ['cod' => '006', 'desc' => 'Acessórios & Ortopedia'],
    'BONFICADOS'          => ['cod' => '007', 'desc' => 'Ofertas & Promocionais'],
    'VAREJINHO'           => ['cod' => '008', 'desc' => 'Cuidados Diários'],
    'GERAL'               => ['cod' => '009', 'desc' => 'Geral'],
    'LIVRE'               => ['cod' => '010', 'desc' => 'Linha Livre'],
    'DIVERSOS'            => ['cod' => '011', 'desc' => 'Diversos'],
    'SERVICOS/DIVERSOS'   => ['cod' => '012', 'desc' => 'Serviços'],
    'Outros'              => ['cod' => '099', 'desc' => 'Outros']
];

$stmtCatCheck = $pdo->prepare("SELECT COUNT(*) AS QTD FROM GRUPOPRODUTO WHERE CODGRUPOPRODUTO = ?");
$stmtCatInsert = $pdo->prepare("INSERT INTO GRUPOPRODUTO (CODGRUPOPRODUTO, DESCRICAO) VALUES (?, ?)");

foreach ($mapaCategorias as $info) {
    $stmtCatCheck->execute([$info['cod']]);
    if ((int)($stmtCatCheck->fetch()['QTD'] ?? 0) === 0) {
        $stmtCatInsert->execute([$info['cod'], $info['desc']]);
    }
}

// 7. Verifica contagem de produtos
$stmtContagem = $pdo->query("SELECT COUNT(*) AS TOTAL FROM PRODUTO");
$totalAtual = (int)($stmtContagem->fetch()['TOTAL'] ?? 0);

$forcarImportacao = (isset($argv) && in_array('--force', $argv)) || ($totalAtual < 100);

if (!$forcarImportacao && $totalAtual > 0) {
    if (php_sapi_name() === 'cli') {
        echo "[STATUS] Banco de dados já contém {$totalAtual} produtos reais do catálogo. Nenhuma reimportação necessária.\n";
    }
    return;
}

if (!file_exists($csvFile)) {
    if (php_sapi_name() === 'cli') {
        echo "[AVISO] Arquivo CSV não encontrado em {$csvFile}. Pulando população em massa.\n";
    }
    return;
}

if (php_sapi_name() === 'cli') {
    echo "[IMPORTACAO] Iniciando população de produtos a partir do CSV...\n";
}

$imagensExistentes = [];
if (is_dir($dirImagensOrigem)) {
    foreach (scandir($dirImagensOrigem) as $f) {
        if ($f === '.' || $f === '..') continue;
        $imagensExistentes[pathinfo($f, PATHINFO_FILENAME)] = $f;
    }
}

$eansDisponiveis = [
    'DORFLEX'      => ['7891010087722', '7891010087807'],
    'NOVALGINA'    => ['7891010618766'],
    'NEOSALDINA'   => ['7891142115492', '7891142982384'],
    'HISTAMIN'     => ['7894916341868'],
    'TORSILAX'     => ['7896004710891'],
    'CIMEGRIPE'    => ['7896004770895'],
    'BUSCOPAN'     => ['7896015592752', '7896104601594'],
    'ENO'          => ['7896902209114', '7896902209138', '7896902210608'],
    'DIPIRONA'     => ['7896914000273', '7897230302898'],
    'LAVITAN'      => ['7898148298068', '7898148299577'],
    'COLAGENO'     => ['7898593052918'],
    'IBUPROFENO'   => ['7899026478909'],
    'PARACETAMOL'  => ['7899095239227'],
    'PANTENE'      => ['7500435127257', '7500435127264'],
    'PROTETOR'     => ['7908733202063']
];

$handle = fopen($csvFile, 'r');
fgetcsv($handle, 0, ';');

$codigosExistentes = [];
$stmtExistentes = $pdo->query("SELECT CODPRODUTO FROM PRODUTO");
while ($r = $stmtExistentes->fetch(PDO::FETCH_NUM)) {
    $codigosExistentes[trim($r[0])] = true;
}
$stmtExistentes->closeCursor();

$stmtMaxEst = $pdo->query("SELECT COALESCE(MAX(CODESTOQUE), 0) AS MAX_ID FROM ESTOQUE");
$codEstoqueAtual = (int)($stmtMaxEst->fetch()['MAX_ID'] ?? 0);
$stmtMaxEst->closeCursor();

if ($pdo->inTransaction()) {
    $pdo->commit();
}

$pdo->beginTransaction();

$stmtInsProd = $pdo->prepare("
    INSERT INTO PRODUTO (CODPRODUTO, DESCRICAOVENDA, PRECOUNITARIOVENDA, STDESATIVADO, CODGRUPOPRODUTO)
    VALUES (?, ?, ?, '0', ?)
");
$stmtInsEst = $pdo->prepare("
    INSERT INTO ESTOQUE (CODESTOQUE, CODPRODUTO, CODORGANIZACAO, QTDEESTOQUE)
    VALUES (?, ?, '001', ?)
");
$stmtInsBarra = $pdo->prepare("
    INSERT INTO PRODUTOCODIGOBARRA (CODPRODUTO, CODIGOBARRA)
    VALUES (?, ?)
");

$inseridos = 0;
$linhaNum = 0;

while (($linha = fgetcsv($handle, 0, ';')) !== false) {
    $linhaNum++;
    $nomeOriginal = trim($linha[0] ?? '');
    $catOriginal = trim($linha[1] ?? 'Outros');
    if ($nomeOriginal === '') continue;

    $codigoCustom = null;
    $nomeLimpo = $nomeOriginal;
    if (preg_match('/^(.*?):(\d+)$/', $nomeOriginal, $m)) {
        $nomeLimpo = trim($m[1]);
        $codigoCustom = $m[2];
    }

    if ($codigoCustom !== null) {
        $codProduto = str_pad($codigoCustom, 10, '0', STR_PAD_LEFT);
    } else {
        $codProduto = str_pad((string)$linhaNum, 10, '0', STR_PAD_LEFT);
    }

    if (isset($codigosExistentes[$codProduto])) {
        continue;
    }
    $codigosExistentes[$codProduto] = true;

    $catInfo = $mapaCategorias[$catOriginal] ?? $mapaCategorias['Outros'];
    $codGrupo = $catInfo['cod'];

    $hash = crc32($nomeLimpo);
    $centavos = abs($hash % 100);
    switch ($catOriginal) {
        case 'GENERICO':
            $reais = 5 + abs($hash % 20);
            break;
        case 'MEDICAMENTOS':
            $reais = 12 + abs($hash % 35);
            break;
        case 'PERFUMARIA':
            $reais = 15 + abs($hash % 45);
            break;
        case 'COMESTIVEIS':
            $reais = 18 + abs($hash % 50);
            break;
        default:
            $reais = 8 + abs($hash % 25);
            break;
    }
    $preco = round($reais + ($centavos / 100), 2);
    if ($preco <= 0) $preco = 9.90;

    $codigoBarra = null;
    foreach ($eansDisponiveis as $termo => &$listaEans) {
        if (!empty($listaEans) && stripos($nomeLimpo, $termo) !== false) {
            $codigoBarra = array_shift($listaEans);
            break;
        }
    }
    unset($listaEans);

    if ($codigoBarra === null) {
        if (isset($imagensExistentes[$codProduto])) {
            $codigoBarra = $codProduto;
        } else {
            $codigoBarra = '789' . str_pad($codProduto, 10, '0', STR_PAD_LEFT);
        }
    }

    $estoqueQtd = 20 + abs($hash % 80);

    $stmtInsProd->execute([
        $codProduto,
        mb_strimwidth($nomeLimpo, 0, 150, '', 'UTF-8'),
        $preco,
        $codGrupo
    ]);

    $codEstoqueAtual++;
    $stmtInsEst->execute([
        $codEstoqueAtual,
        $codProduto,
        $estoqueQtd
    ]);

    try {
        $stmtInsBarra->execute([$codProduto, $codigoBarra]);
    } catch (Exception $e) {
    }

    $inseridos++;
}

fclose($handle);

if ($pdo->inTransaction()) {
    $pdo->commit();
}

if (php_sapi_name() === 'cli') {
    echo "[SUCESSO] Inicialização concluída: {$inseridos} novos produtos cadastrados no Firebird!\n";
}
