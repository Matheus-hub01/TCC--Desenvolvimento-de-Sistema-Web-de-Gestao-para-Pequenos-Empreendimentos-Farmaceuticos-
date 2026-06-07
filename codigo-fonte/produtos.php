<?php
session_start();
require_once 'conexao.php';

$busca = trim($_GET['busca'] ?? '');
$categoriaSelecionada = trim($_GET['categoria'] ?? '');
$indicacaoSelecionada = trim($_GET['indicacao'] ?? '');

$clienteLogado = $_SESSION['cliente_logado'] ?? null;

/*
|--------------------------------------------------------------------------
| Busca as categorias para preencher o filtro
|--------------------------------------------------------------------------
*/
$sqlCategorias = "
    SELECT
        G.CODGRUPOPRODUTO,
        G.DESCRICAO
    FROM GRUPOPRODUTO G
    WHERE G.DESCRICAO IS NOT NULL
    ORDER BY G.DESCRICAO
";

$categorias = $pdo->query($sqlCategorias)->fetchAll();

/*
|--------------------------------------------------------------------------
| Monta os filtros dos produtos
|--------------------------------------------------------------------------
*/
$filtros = [
    "COALESCE(P.STDESATIVADO, '0') <> '1'",
    "P.PRECOUNITARIOVENDA > 0",
    "E.CODORGANIZACAO = '001'"
];

$parametros = [];

if ($busca !== '') {
    $filtros[] = "(
        UPPER(P.DESCRICAOVENDA) LIKE UPPER(:busca_nome)
        OR CAST(P.CODPRODUTO AS VARCHAR(30)) = :busca_codigo
        OR EXISTS (
            SELECT 1
            FROM PRODUTOCODIGOBARRA CB
            WHERE CB.CODPRODUTO = P.CODPRODUTO
              AND CB.CODIGOBARRA = :busca_barra
        )
    )";

    $parametros[':busca_nome'] = '%' . $busca . '%';
    $parametros[':busca_codigo'] = $busca;
    $parametros[':busca_barra'] = $busca;
}

if ($categoriaSelecionada !== '') {
    $filtros[] = "P.CODGRUPOPRODUTO = :categoria";
    $parametros[':categoria'] = $categoriaSelecionada;
}


/*
|--------------------------------------------------------------------------
| Filtro por indicação escolhida no menu lateral
|--------------------------------------------------------------------------
| O filtro é feito pelo nome do produto, pois o banco atual
| não possui uma coluna específica de indicação.
*/
$indicacoes = [
    'anti-inflamatorios' => [
        'IBUPROFENO',
        'NIMESULIDA',
        'DICLOFENACO',
        'CETOPROFENO',
        'NAPROXENO'
    ],

    'dor-de-garganta' => [
        'BENALET',
        'STREPSILS',
        'PASTILH',
        'GARGANTA',
        'HEXOMEDINE'
    ],

    'gripe-resfriado' => [
        'BENEGRIP',
        'CIMEGRIPE',
        'MULTIGRIP',
        'ANTIGRIPAL',
        'CORISTINA'
    ],

    'dor-febre' => [
        'DIPIRONA',
        'NOVALGINA',
        'PARACETAMOL',
        'TYLENOL'
    ],

    'gastrite' => [
        'OMEPRAZOL',
        'PANTOPRAZOL',
        'ESTOMAZIL'
    ],

    'tosse' => [
        'XAROPE',
        'EXPECTORANTE',
        'AMBROXOL',
        'ACETILCISTEINA'
    ] ,


    /* VIDA SAUDÁVEL */
    'vitamina-d' => [ 
        'VITAMINA D',
        'VIT D',
        'D3',
        'COLECALCIFEROL'
    ],

    'vitamina-c' => [
        'VITAMINA C',
        'VIT C',
        'ACIDO ASCORBICO',
        'ÁCIDO ASCÓRBICO'
    ],

    'omega-3' => [
        'OMEGA 3',
        'ÔMEGA 3',
        'OMEGA3',
        'OLEO DE PEIXE',
        'ÓLEO DE PEIXE'
    ],

    'colageno' => [
        'COLAGENO',
        'COLÁGENO'
    ],

    'barra-proteina' => [
        'BARRA DE PROTEINA',
        'BARRA DE PROTEÍNA',
        'BARRA PROTEINA',
        'BARRA PROTEÍNA',
        'PROTEIN BAR',
        'PROTEINA',
        'PROTEÍNA'
    ],

    'whey-protein' => [
        'WHEY',
        'WHEY PROTEIN',
        'PROTEIN'
    ],

    'creatina' => [
        'CREATINA'
    ],

    'bcaa' => [
        'BCAA'
    ],

    /* BELEZA */
    'esmaltes' => [
        'ESMALTE',
        'ESMALTES'
    ],

    'hidratantes' => [
        'HIDRATANTE',
        'HIDRATANTES',
        'LOCAO',
        'LOÇÃO',
        'CREME'
    ],

    'protetor-solar' => [
        'PROTETOR SOLAR',
        'FILTRO SOLAR',
        'FPS',
        'SUNSCREEN',
        'SOLAR'
    ],

    'shampoo' => [
        'SHAMPOO',
        'XAMPU'
    ],

    /* HIGIENE PESSOAL */
    'sabonete' => [
        'SABONETE',
        'SABONETES'
    ],

    'desodorante' => [
        'DESODORANTE',
        'ANTITRANSPIRANTE',
        'ANTIPERSPIRANTE'
    ],

    'pasta-dente' => [
        'PASTA DE DENTE',
        'CREME DENTAL',
        'DENTAL',
        'COLGATE',
        'SORRISO',
        'ORAL'
    ],

    'escova-dente' => [
        'ESCOVA DE DENTE',
        'ESCOVA DENTAL',
        'ESCOVA'
    ],

    'fio-dental' => [
        'FIO DENTAL',
        'FITA DENTAL'
    ]
];

if ($indicacaoSelecionada !== '' && isset($indicacoes[$indicacaoSelecionada])) {
    $condicoesIndicacao = [];

    foreach ($indicacoes[$indicacaoSelecionada] as $indice => $termo) {
        $parametro = ':indicacao_' . $indice;

        $condicoesIndicacao[] = "UPPER(P.DESCRICAOVENDA) LIKE " . $parametro;
        $parametros[$parametro] = '%' . $termo . '%';
    }

    $filtros[] = '(' . implode(' OR ', $condicoesIndicacao) . ')';
}



/*
|--------------------------------------------------------------------------
| Consulta diretamente o Firebird
|--------------------------------------------------------------------------
| FIRST 60 evita carregar milhares de produtos de uma vez na tela.
*/
$sqlProdutos = "
    SELECT FIRST 60
        P.CODPRODUTO,
        P.DESCRICAOVENDA AS NOME_PRODUTO,
        P.PRECOUNITARIOVENDA AS PRECO,
        COALESCE(G.DESCRICAO, 'Outros') AS CATEGORIA,
        SUM(E.QTDEESTOQUE) AS ESTOQUE,
        (
            SELECT FIRST 1 CB.CODIGOBARRA
            FROM PRODUTOCODIGOBARRA CB
            WHERE CB.CODPRODUTO = P.CODPRODUTO
        ) AS CODIGOBARRA
    FROM PRODUTO P
    INNER JOIN ESTOQUE E
        ON E.CODPRODUTO = P.CODPRODUTO
    LEFT JOIN GRUPOPRODUTO G
        ON G.CODGRUPOPRODUTO = P.CODGRUPOPRODUTO
    WHERE " . implode(' AND ', $filtros) . "
    GROUP BY
        P.CODPRODUTO,
        P.DESCRICAOVENDA,
        P.PRECOUNITARIOVENDA,
        G.DESCRICAO
    HAVING SUM(E.QTDEESTOQUE) > 0
    ORDER BY P.DESCRICAOVENDA
";

$stmt = $pdo->prepare($sqlProdutos);
$stmt->execute($parametros);
$produtos = $stmt->fetchAll();

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}


 function formatarNomeProduto(string $nome, string $codigoProduto = '', string $codigoBarra = ''): string
{
    $nome = trim($nome);
    $codigoProduto = trim((string) $codigoProduto);
    $codigoBarra = trim((string) $codigoBarra); 

    // Remove código de barras no início do nome
    if ($codigoBarra !== '' && strpos($nome, $codigoBarra) === 0) {
        $nome = substr($nome, strlen($codigoBarra));
    }

    // Remove código do produto no início do nome
    if ($codigoProduto !== '' && strpos($nome, $codigoProduto) === 0) {
        $nome = substr($nome, strlen($codigoProduto));
    }

    // Remove números longos no começo: 7896025Bloco...
    $nome = preg_replace('/^\s*\d{5,}\s*/u', '', $nome);

    // Corrige casos como 0Oleo -> Oleo
    $nome = preg_replace('/^\s*0+(?=[A-Za-zÀ-ÿ])/u', '', $nome);

    // Coloca espaço entre número e letra quando vier grudado
    $nome = preg_replace('/(?<=[A-Za-zÀ-ÿ])(?=\d)/u', ' ', $nome);
    $nome = preg_replace('/(?<=\d)(?=[A-Za-zÀ-ÿ])/u', ' ', $nome);

    // Padroniza espaços ao redor de +
    $nome = preg_replace('/\s*\+\s*/u', ' + ', $nome);

    // Deixa o texto em formato bonito
    $nome = mb_strtolower($nome, 'UTF-8');
    $nome = mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');

    /*
    |--------------------------------------------------------------------------
    | Abreviações comuns de produtos de farmácia
    |--------------------------------------------------------------------------
    */

    // C/ e S/
    $nome = preg_replace('/\bC\/\s*/iu', 'com ', $nome);
    $nome = preg_replace('/\bS\/\s*/iu', 'sem ', $nome);

    // Caixa / CX
    $nome = preg_replace('/\bCx\.?\s*(?=\d)/iu', '', $nome);
    $nome = preg_replace('/\bCx\.?\b/iu', 'caixa', $nome);

    // Comprimidos
    $nome = preg_replace('/(\d+)\s*(Cpr|Comp|Comps|Cp)\b\.?/iu', '$1 comprimidos', $nome);
    $nome = preg_replace('/\b(Cpr|Comp|Comps|Cp)\b\.?/iu', 'comprimidos', $nome);

    // Cápsulas
    $nome = preg_replace('/(\d+)\s*(Caps|Cap|Cps|Cáps)\b\.?/iu', '$1 cápsulas', $nome);
    $nome = preg_replace('/\b(Caps|Cap|Cps|Cáps)\b\.?/iu', 'cápsulas', $nome);

    // Unidades
    $nome = preg_replace('/(\d+)\s*(Unid|Und|Un)\b\.?/iu', '$1 unidades', $nome);
    $nome = preg_replace('/\b(Unid|Und|Un)\b\.?/iu', 'unidade', $nome);

    // Medidas
    $nome = preg_replace('/(\d+)\s*Mg\/Ml\b/iu', '$1mg/mL', $nome);
    $nome = preg_replace('/(\d+)\s*Mg\b/iu', '$1mg', $nome);
    $nome = preg_replace('/(\d+)\s*Mcg\b/iu', '$1mcg', $nome);
    $nome = preg_replace('/(\d+)\s*Ml\b/iu', '$1mL', $nome);
    $nome = preg_replace('/(\d+)\s*G\b/iu', '$1g', $nome);

    // Gotas
    $nome = preg_replace('/\b(Gts|Gt|Gd)\b\.?/iu', 'gotas', $nome);

    // Embalagens
    $nome = preg_replace('/\bFrs\b\.?/iu', 'frascos', $nome);
    $nome = preg_replace('/\bFr\b\.?/iu', 'frasco', $nome);
    $nome = preg_replace('/\bBisn\b\.?/iu', 'bisnaga', $nome);
    $nome = preg_replace('/\bEnv\b\.?/iu', 'envelope', $nome);

    // Sachê / sachês
    $nome = preg_replace('/(\d+)\s*(Sache|Sachê|Sach|Saches)\b\.?/iu', '$1 sachês', $nome);
    $nome = preg_replace('/\b(Sache|Sachê|Sach)\b\.?/iu', 'sachê', $nome);

    // Blister / display
    $nome = preg_replace('/\bBl\b\.?/iu', 'blister', $nome);
    $nome = preg_replace('/\bDisp\b\.?/iu', 'display', $nome);

    // Características do medicamento
    $nome = preg_replace('/\bRev\b\.?/iu', 'revestidos', $nome);
    $nome = preg_replace('/\bEferv\b\.?/iu', 'efervescente', $nome);
    $nome = preg_replace('/\bMast\b\.?/iu', 'mastigável', $nome);
    $nome = preg_replace('/\bSol\b\.?/iu', 'solução', $nome);
    $nome = preg_replace('/\bSusp\b\.?/iu', 'suspensão', $nome);
    $nome = preg_replace('/\bXpe\b\.?/iu', 'xarope', $nome);
    $nome = preg_replace('/\bInj\b\.?/iu', 'injetável', $nome);

    // Sabores / cores / público
    $nome = preg_replace('/\b(Sb|Sab)\b\.?/iu', 'sabor', $nome);
    $nome = preg_replace('/\bVerd\b\.?/iu', 'verdes', $nome);
    $nome = preg_replace('/\bAmar\b\.?/iu', 'amarelos', $nome);
    $nome = preg_replace('/\bAdul\b\.?/iu', 'adulto', $nome);
    $nome = preg_replace('/\bPed\b\.?/iu', 'pediátrico', $nome);
    $nome = preg_replace('/\bInf\b\.?/iu', 'infantil', $nome);

    // Genéricos e formas comuns
    $nome = preg_replace('/\bGen\b\.?/iu', 'genérico', $nome);
    $nome = preg_replace('/\bMono\b\.?/iu', 'monoidratada', $nome);
    $nome = preg_replace('/\bMonoid\b\.?/iu', 'monoidratada', $nome);

    // Arruma "X" entre números: 24 X 5 -> 24 x 5
    $nome = preg_replace('/(\d+)\s*X\s*(\d+)/iu', '$1 x $2', $nome);

    // Palavras pequenas ficam melhores em minúsculo
    $palavrasMinusculas = [
        ' De ' => ' de ',
        ' Da ' => ' da ',
        ' Do ' => ' do ',
        ' Das ' => ' das ',
        ' Dos ' => ' dos ',
        ' E ' => ' e ',
        ' Para ' => ' para ',
        ' Com ' => ' com ',
        ' Sem ' => ' sem ',
        ' Sabor ' => ' sabor ',
        ' Por ' => ' por '
    ];

    $nome = str_replace(
        array_keys($palavrasMinusculas),
        array_values($palavrasMinusculas),
        $nome
    );

    // Remove espaços duplicados
    $nome = preg_replace('/\s+/', ' ', trim($nome));

    // Primeira letra maiúscula
    return mb_strtoupper(mb_substr($nome, 0, 1, 'UTF-8'), 'UTF-8') .
           mb_substr($nome, 1, null, 'UTF-8');
}

function imagemProduto($codigoProduto, $codigoBarra = ''): string
{
    $pastaServidor = __DIR__ . '/img/produtos/';
    $pastaSite = 'img/produtos/';

    $extensoes = ['jpg', 'jpeg', 'png', 'webp'];

    $possiveisNomes = [];

    if (!empty($codigoBarra)) {
        $possiveisNomes[] = trim($codigoBarra);
    }

    if (!empty($codigoProduto)) {
        $possiveisNomes[] = trim($codigoProduto);
    }

    foreach ($possiveisNomes as $nome) {
        foreach ($extensoes as $extensao) {
            $arquivoServidor = $pastaServidor . $nome . '.' . $extensao;
            $arquivoSite = $pastaSite . $nome . '.' . $extensao;

            if (file_exists($arquivoServidor)) {
                return $arquivoSite;
            }
        }
    }

    return 'img/produtos/sem-imagem.png';
}




?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos | Farmácia Online</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="topo">
    <div class="topo-container">
        <div class="topo-esquerda">
            <a href="produtos.php" class="logo">
                <img 
                    src="img/logo-pharmapaz.png" 
                    alt="Drogaria PharmaPaz" 
                    class="logo-img"
                >
            </a>
        </div>
        
        <button class="menu-toggle" onclick="abrirMenuCategorias()">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6" stroke-width="2"></line>
                    <line x1="3" y1="12" x2="21" y2="12" stroke-width="2"></line>
                    <line x1="3" y1="18" x2="21" y2="18" stroke-width="2"></line>
                </svg>
                <span>Menu</span>
            </button>

        <form method="GET" action="produtos.php" class="busca-topo">
            <input
                type="text"
                name="busca"
                placeholder="O que você precisa?"
                value="<?= escapar($busca) ?>"
                autocomplete="off"
            >

            <button type="submit" aria-label="Buscar produto">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="16.5" y1="16.5" x2="21" y2="21"></line>
                </svg>
            </button>
        </form>

        <div class="topo-direita">
            

            <a href="#" 
                 onclick="<?= $clienteLogado ? 'abrirPerfilUsuario(event)' : 'abrirLoginCadastro(event)' ?>" 
                 class="cliente-acesso">

                <svg class="cliente-icone" viewBox="0 0 48 48" aria-hidden="true">
                    <circle cx="24" cy="14" r="10"></circle>
                    <path d="M8 43V35C8 29.5 12.5 25 18 25H30C35.5 25 40 29.5 40 35V43Z"></path>
                </svg>


                     <div class="cliente-texto">
                         <?php if ($clienteLogado): ?>
                            <span>Perfil</span>
                            <strong><?= escapar($clienteLogado['nome']) ?></strong>
                         <?php else: ?>
                            <span>Bem-vindo(a),</span>
                            <strong>Faça seu Login ou Cadastro</strong>
                        <?php endif; ?>
                    </div>

                 
            </a>

            <nav class="menu">
                
            
                <a href="#" onclick="abrirCarrinho(event)" class="menu-carrinho" aria-label="Carrinho">
                    <svg class="icone-carrinho" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M10 16H38L34.5 31H13.5L10 16Z"></path>
                        <path d="M16 16L20 10H28L32 16"></path>
                        <line x1="18" y1="35" x2="18" y2="35"></line>
                        <line x1="30" y1="35" x2="30" y2="35"></line>
                    </svg>
                </a>
            </nav>
        </div>
    </div>





    <!-- Menu Lateral (Drawer) -->
<div class="menu-drawer" id="menuDrawer">
    <div class="menu-drawer-content">

        <button class="menu-close" onclick="fecharMenuCategorias()">
            &times;
        </button>

        <div class="menu-drawer-grid menu-remedios">

            <a href="produtos.php" class="menu-titulo-medicamentos">
                Medicamentos
            </a>

            <h4 class="menu-subtitulo">Remédios</h4>

            <a 
                href="produtos.php?indicacao=anti-inflamatorios"
                class="menu-indicacao <?= $indicacaoSelecionada === 'anti-inflamatorios' ? 'ativo' : '' ?>"
                onclick="fecharMenuCategorias()"
            >
                Anti-inflamatórios
            </a>

            <a 
                href="produtos.php?indicacao=dor-de-garganta"
                class="menu-indicacao <?= $indicacaoSelecionada === 'dor-de-garganta' ? 'ativo' : '' ?>"
                onclick="fecharMenuCategorias()"
            >
                Para dor de garganta
            </a>

            <a 
                href="produtos.php?indicacao=gripe-resfriado"
                class="menu-indicacao <?= $indicacaoSelecionada === 'gripe-resfriado' ? 'ativo' : '' ?>"
                onclick="fecharMenuCategorias()"
            >
                Para gripe e resfriado
            </a>

            <a 
                href="produtos.php?indicacao=dor-febre"
                class="menu-indicacao <?= $indicacaoSelecionada === 'dor-febre' ? 'ativo' : '' ?>"
                onclick="fecharMenuCategorias()"
            >
                Para dor e febre
            </a>

            <a 
                href="produtos.php?indicacao=gastrite"
                class="menu-indicacao <?= $indicacaoSelecionada === 'gastrite' ? 'ativo' : '' ?>"
                onclick="fecharMenuCategorias()"
            >
                Para gastrite
            </a>

            <a 
                href="produtos.php?indicacao=tosse"
                class="menu-indicacao <?= $indicacaoSelecionada === 'tosse' ? 'ativo' : '' ?>"
                onclick="fecharMenuCategorias()"
            >
                Para tosse
            </a>

        </div>


        <!-- VIDA SAUDÁVEL -->
<div class="menu-coluna-categoria">
    <a href="#" class="menu-titulo-medicamentos">
        Vida Saudável
    </a>

    <h4 class="menu-subtitulo">Vitaminas</h4>

    <a href="produtos.php?indicacao=vitamina-d"
       class="menu-indicacao <?= $indicacaoSelecionada === 'vitamina-d' ? 'ativo' : '' ?>">
       
        Vitamina D
    </a>

    <a href="produtos.php?indicacao=vitamina-c"
       class="menu-indicacao <?= $indicacaoSelecionada === 'vitamina-c' ? 'ativo' : '' ?>">
       
       Vitamina C
    </a>

    <a href="produtos.php?indicacao=omega-3"
       class="menu-indicacao <?= $indicacaoSelecionada === 'omega-3' ? 'ativo' : '' ?>">
       
       Ômega 3
    </a>

    <a href="produtos.php?indicacao=colageno"
       class="menu-indicacao <?= $indicacaoSelecionada === 'colageno' ? 'ativo' : '' ?>">
    
       Colágeno
    </a>

    <h4 class="menu-subtitulo subtitulo-espacado">Pré e pós treino</h4>

    <a href="produtos.php?indicacao=barra-proteina"
       class="menu-indicacao <?= $indicacaoSelecionada === 'barra-proteina' ? 'ativo' : '' ?>">
        Barras de proteína
    </a>

    <a href="produtos.php?indicacao=whey-protein"
       class="menu-indicacao <?= $indicacaoSelecionada === 'whey-protein' ? 'ativo' : '' ?>">
        Whey Protein
    </a>

    <a href="produtos.php?indicacao=creatina"
       class="menu-indicacao <?= $indicacaoSelecionada === 'creatina' ? 'ativo' : '' ?>">
        Creatina
    </a>

    <a href="produtos.php?indicacao=bcaa"
       class="menu-indicacao <?= $indicacaoSelecionada === 'bcaa' ? 'ativo' : '' ?>">
        BCAA
    </a>
</div>


<!-- BELEZA -->
<div class="menu-coluna-categoria">
    <a href="#" class="menu-titulo-medicamentos">
        Beleza
    </a>

    <h4 class="menu-subtitulo">Cuidados</h4>

    <a href="produtos.php?indicacao=esmaltes"
       class="menu-indicacao <?= $indicacaoSelecionada === 'esmaltes' ? 'ativo' : '' ?>">
        Esmaltes
    </a>

    <a href="produtos.php?indicacao=hidratantes"
       class="menu-indicacao <?= $indicacaoSelecionada === 'hidratantes' ? 'ativo' : '' ?>">
        Hidratantes
    </a>

    <a href="produtos.php?indicacao=protetor-solar"
       class="menu-indicacao <?= $indicacaoSelecionada === 'protetor-solar' ? 'ativo' : '' ?>">
        Protetor solar
    </a>

    <a href="produtos.php?indicacao=shampoo"
       class="menu-indicacao <?= $indicacaoSelecionada === 'shampoo' ? 'ativo' : '' ?>">
        Shampoo
    </a>
</div>


<!-- HIGIENE PESSOAL -->
<div class="menu-coluna-categoria">
    <a href="#" class="menu-titulo-medicamentos">
        Higiene Pessoal
    </a>

    <h4 class="menu-subtitulo">Cuidados diários</h4>

    <a href="produtos.php?indicacao=sabonete"
       class="menu-indicacao <?= $indicacaoSelecionada === 'sabonete' ? 'ativo' : '' ?>">
        Sabonetes
    </a>

    <a href="produtos.php?indicacao=desodorante"
       class="menu-indicacao <?= $indicacaoSelecionada === 'desodorante' ? 'ativo' : '' ?>">
        Desodorantes
    </a>

    <a href="produtos.php?indicacao=pasta-dente"
       class="menu-indicacao <?= $indicacaoSelecionada === 'pasta-dente' ? 'ativo' : '' ?>">
        Pasta de dente
    </a>

    <a href="produtos.php?indicacao=escova-dente"
       class="menu-indicacao <?= $indicacaoSelecionada === 'escova-dente' ? 'ativo' : '' ?>">
        Escova de dente
    </a>

    <a href="produtos.php?indicacao=fio-dental"
       class="menu-indicacao <?= $indicacaoSelecionada === 'fio-dental' ? 'ativo' : '' ?>">
        Fio dental
    </a>
</div>









    </div>
</div>

<div class="menu-overlay" id="menuOverlay" onclick="fecharMenuCategorias()"></div>

   






<section class="banner">
    <div class="banner-conteudo">
        <h2>Encontre seus produtos</h2>
        
    </div>
</section>


<main class="conteudo">

    <section class="filtros">
        <form method="GET" action="produtos.php" class="form-filtros">
            
            <?php if ($busca !== ''): ?>
    <input type="hidden" name="busca" value="<?= escapar($busca) ?>">
<?php endif; ?>

            <?php if ($categoriaSelecionada !== ''): ?>
    <input type="hidden" name="categoria" value="<?= escapar($categoriaSelecionada) ?>">
<?php endif; ?>

<?php if ($indicacaoSelecionada !== ''): ?>
    <input type="hidden" name="indicacao" value="<?= escapar($indicacaoSelecionada) ?>">
<?php endif; ?>


            <button type="button" class="atendimento-card" onclick="abrirAtendimento()">
    <span class="atendimento-icone">
        <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 13a8 8 0 0 1 16 0"></path>
        <path d="M4 13v4a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2"></path>
        <path d="M20 13v4a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2"></path>
        <path d="M17 19c0 1.1-.9 2-2 2h-3"></path>
    </svg>
    </span>

    <span class="atendimento-texto">
        <strong>Atendimento</strong>
        <small>Tire dúvidas antes de comprar</small>
    </span>
</button>

<a href="produtos.php" class="botao botao-limpar">
    Limpar
</a>


        </form>
    </section>

    <div class="resultado-topo">
        <h3>Produtos disponíveis</h3>
        <p><?= count($produtos) ?> produto(s) exibido(s)</p>
    </div>

    <?php if (count($produtos) > 0): ?>
        <section class="grade-produtos">

            <?php foreach ($produtos as $produto): ?>
                <article class="card-produto">
                   <div class="produto-imagem">
    <img 
        src="<?= escapar(imagemProduto($produto['CODPRODUTO'], $produto['CODIGOBARRA'] ?? '')) ?>" 
        alt="<?= escapar(trim($produto['NOME_PRODUTO'])) ?>"
    >
</div>

                    <div class="produto-info">
                        <span class="categoria">
                            <?= escapar(trim($produto['CATEGORIA'])) ?>
                        </span>

                        <h4 class="produto-nome">
                            <?= escapar(formatarNomeProduto(
                                 $produto['NOME_PRODUTO'],
                                 $produto['CODPRODUTO'],
                                 $produto['CODIGOBARRA'] ?? ''
                         )) ?>
                        </h4>

                        <p class="produto-codigo">
                            Código: <?= escapar(trim($produto['CODPRODUTO'])) ?>
                        </p>

                        <p class="preco">
                            R$ <?= number_format((float) $produto['PRECO'], 2, ',', '.') ?>
                        </p>

                        <p class="estoque">
                            Disponível: <?= number_format((float) $produto['ESTOQUE'], 0, ',', '.') ?> unidade(s)
                        </p>

                        <a 
                  href="#"
                  class="detalhes botao-comprar"
                  data-codigo="<?= escapar(trim($produto['CODPRODUTO'])) ?>"
                  data-nome="<?= escapar(formatarNomeProduto(
                      $produto['NOME_PRODUTO'],
                      $produto['CODPRODUTO'],
                      $produto['CODIGOBARRA'] ?? ''
                  )) ?>"
                  data-preco="<?= number_format((float) $produto['PRECO'], 2, '.', '') ?>"
                  data-imagem="<?= escapar(imagemProduto($produto['CODPRODUTO'], $produto['CODIGOBARRA'] ?? '')) ?>"
>
                       Comprar  
              </a>


                    </div>
                </article>
            <?php endforeach; ?>

        </section>
    <?php else: ?>
        <section class="sem-produtos">
            <h3>Nenhum produto encontrado</h3>
            <p>Tente buscar por outro nome ou selecionar outra categoria.</p>
        </section>
    <?php endif; ?>

</main>


<footer class="rodape">
    <p>Farmácia PharmaPaz • Projeto acadêmico integrado ao banco Firebird</p>
</footer>

<!-- Modal Popup Carrinho Vazio -->
<div class="modal-overlay" id="modalCarrinhoVazio">
    <div class="modal-content">
        <button class="modal-close" onclick="fecharModal()">&times;</button>
        
        <div class="modal-icon">
            <svg viewBox="0 0 64 64" aria-hidden="true">
                <path d="M14 24H50L45.5 43H18.5L14 24Z"></path>
                <path d="M22 24L27 16H37L42 24"></path>
                <circle cx="24" cy="48" r="1.8"></circle>
                <circle cx="40" cy="48" r="1.8"></circle>
            </svg>
        </div>

        <h2 class="modal-title">Seu carrinho está vazio</h2>
        <p class="modal-subtitle">Que tal aproveitar nossas ofertas do dia?</p>

        <a href="#" onclick="fecharModal(); return false;" class="modal-button">Continuar comprando</a>
    </div>
</div>


<style>
    
    /* Modal Popup Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .modal-overlay.ativo {
        display: flex;
        align-items: flex-start;
        justify-content: flex-end;
        padding-top: 20px;
        padding-right: 20px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .modal-content {
        background: #ffffff;
        border-radius: 32px;
        padding: 50px 40px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        text-align: center;
        position: relative;
        animation: slideInRight 0.4s ease;
        border: 1px solid #edf1f2;
        overflow-y: auto;
    }

    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        font-size: 28px;
        color: #9ca3af;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: 0.2s;
    }

    .modal-close:hover {
        background: #f3f4f6;
        color: #1f2937;
    }

    .modal-icon {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        margin: 0 auto 30px;
        background: linear-gradient(135deg, #e7f7f6, #f1fbfa);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .modal-icon svg {
        width: 70px;
        height: 70px;
        fill: none;
        stroke: var(--verde-principal);
        stroke-width: 2.2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .modal-title {
        font-size: 28px;
        color: #1f2937;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .modal-subtitle {
        font-size: 16px;
        color: #6b7280;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    .modal-button {
        background: linear-gradient(135deg, #009f9a, #007e79);
        color: #ffffff;
        border: none;
        padding: 14px 40px;
        border-radius: 28px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 8px 22px rgba(0, 143, 137, 0.22);
        transition: 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .modal-button:hover {
        transform: translateY(-2px);
        filter: brightness(1.05);
        box-shadow: 0 12px 28px rgba(0, 143, 137, 0.28);
    }

    /* Estilos adicionais para Login/Cadastro */
    .modal-auth {
        max-width: 540px !important;
        padding: 50px 35px !important;
    }

    .auth-screen {
        animation: slideInRight 0.4s ease;
    }

    .voltar-link {
        display: inline-block;
        color: var(--verde-principal);
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 20px;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s;
    }

    .voltar-link:hover {
        color: #007e79;
    }

    .auth-botoes {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .modal-button-primary {
        background: linear-gradient(135deg, #009f9a, #007e79);
        color: #ffffff;
        width: 100%;
    }

    .modal-button-secondary {
        background: #ffffff;
        color: var(--verde-principal);
        border: 2px solid #e0e7ff;
        box-shadow: none;
    }

    .modal-button-secondary:hover {
        background: #f9fafb;
        border-color: var(--verde-principal);
    }

    .auth-form {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .form-group {
        text-align: left;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-group input {
        width: 100%;
        padding: 12px 12px 12px 45px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        font-size: 14px;
        transition: 0.2s;
        background: #ffffff;
    }

    .input-group input:focus {
        outline: none;
        border-color: var(--verde-principal);
        box-shadow: 0 0 0 3px rgba(0, 159, 154, 0.1);
    }

    .input-icon {
        position: absolute;
        left: 12px;
        width: 20px;
        height: 20px;
        color: #9ca3af;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .toggle-senha {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 18px;
        padding: 0;
        color: #6b7280;
    }

    .toggle-senha:hover {
        color: var(--verde-principal);
    }

    .forca-senha {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
        font-weight: 500;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .form-row .form-group {
        margin: 0;
    }

    .form-row .input-group input {
        width: 100%;
        min-width: 0;
    }

    @media (max-width: 600px) {
        .modal-content {
            padding: 40px 25px;
            border-radius: 24px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .modal-auth {
            max-width: 100%;
        }
    }
</style>

<!-- Modal Popup Carrinho Vazio -->
<div class="modal-overlay" id="modalCarrinhoVazio">
    <div class="modal-content">
        <button class="modal-close" onclick="fecharModal()">&times;</button>
        
        <div class="modal-icon">
            <svg viewBox="0 0 64 64" aria-hidden="true">
                <path d="M14 24H50L45.5 43H18.5L14 24Z"></path>
                <path d="M22 24L27 16H37L42 24"></path>
                <circle cx="24" cy="48" r="1.8"></circle>
                <circle cx="40" cy="48" r="1.8"></circle>
            </svg>
        </div>

        <h2 class="modal-title">Sua cesta está vazia</h2>
        <p class="modal-subtitle">Que tal aproveitar nossas ofertas do dia?</p>

        <a href="#" onclick="fecharModal(); return false;" class="modal-button">Continuar comprando</a>
    </div>
</div>

<!-- Modal Login/Cadastro -->
<div class="modal-overlay" id="modalLoginCadastro">
    <div class="modal-content modal-auth">
        <button class="modal-close" onclick="fecharLoginCadastro()">&times;</button>
        
        <!-- Tela de Boas-vindas (Inicial) -->
        <div id="telaBoasVindas" class="auth-screen">
            <div class="modal-icon">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <defs>
                        <linearGradient id="redGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#EF4444;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#DC2626;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Cruz vermelha - barra horizontal -->
                    <rect x="25" y="50" width="70" height="20" rx="3" fill="url(#redGrad)"/>
                    
                    <!-- Cruz vermelha - barra vertical -->
                    <rect x="55" y="25" width="20" height="70" rx="3" fill="url(#redGrad)"/>
                    
                    <!-- Pomba branca -->
                    <g fill="#FFFFFF">
                        <!-- Cabeça -->
                        <circle cx="60" cy="42" r="6"/>
                        
                        <!-- Corpo -->
                        <ellipse cx="60" cy="62" rx="9" ry="10"/>
                        
                        <!-- Asa esquerda -->
                        <path d="M 52 58 Q 38 55 35 65 Q 42 60 52 62 Z" fill="#FFFFFF"/>
                        
                        <!-- Asa direita -->
                        <path d="M 68 58 Q 82 55 85 65 Q 78 60 68 62 Z" fill="#FFFFFF"/>
                        
                        <!-- Cauda superior esquerda -->
                        <path d="M 54 70 L 50 80" stroke="#FFFFFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                        
                        <!-- Cauda central -->
                        <path d="M 60 72 L 60 82" stroke="#FFFFFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                        
                        <!-- Cauda superior direita -->
                        <path d="M 66 70 L 70 80" stroke="#FFFFFF" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </g>
                </svg>
            </div>

            <h2 class="modal-title">Boas-vindas!</h2>
            <p class="modal-subtitle">Faça seu Login ou cadastro</p>

            <div class="auth-botoes">
                <button onclick="mostrarLogin()" class="modal-button modal-button-primary">
                    Entrar
                </button>
                <button onclick="mostrarCadastro()" class="modal-button modal-button-secondary">
                    Cadastrar
                </button>
            </div>
        </div>

        <!-- Tela de Login -->
        <div id="telaLogin" class="auth-screen" style="display: none;">
            <a href="#" onclick="voltarBoasVindas(); return false;" class="voltar-link">← Voltar</a>
            
            <h2 class="modal-title">Entrar</h2>
            
            <form class="auth-form" method="POST" action="login_usuario.php">
                <div class="form-group">
                    <label>E-mail *</label>
                    <div class="input-group">
                        <svg viewBox="0 0 24 24" class="input-icon">
                            <path d="M4 6h16v12H4z"></path>
                            <path d="M4 6l8 5 8-5"></path>
                        </svg>
                        <input type="email" name="email" placeholder="Digite seu e-mail" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Senha *</label>
                    <div class="input-group">
                        <svg viewBox="0 0 24 24" class="input-icon">
                            <path d="M12 1C6.48 1 2 5.48 2 11v9h20v-9c0-5.52-4.48-10-10-10zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3z"></path>
                        </svg>
                        <input type="password" name="senha" placeholder="Digite sua senha" required>
                        <button type="button" class="toggle-senha">👁</button>
                    </div>
                </div>

                <button type="submit" class="modal-button modal-button-primary" style="width: 100%;">
                    Entrar
                </button>
            </form>
        </div>

        <!-- Tela de Cadastro -->
        <div id="telaCadastro" class="auth-screen" style="display: none;">
            <a href="#" onclick="voltarBoasVindas(); return false;" class="voltar-link">← Voltar</a>
            
            <h2 class="modal-title">Cadastrar</h2>


            
            
            <form class="auth-form" method="POST" action="cadastrar_usuario.php"> 
                <div class="form-group">
                    <label>E-mail *</label>
                    <div class="input-group">
                        <svg viewBox="0 0 24 24" class="input-icon">
                            <path d="M4 6h16v12H4z"></path>
                            <path d="M4 6l8 5 8-5"></path>
                        </svg>
                        <input type="email" name="email" placeholder="Digite seu e-mail" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Senha *</label>
                    <div class="input-group">
                        <svg viewBox="0 0 24 24" class="input-icon">
                            <path d="M12 1C6.48 1 2 5.48 2 11v9h20v-9c0-5.52-4.48-10-10-10zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3z"></path>
                        </svg>
                        <input type="password" name="senha" placeholder="Digite sua senha" required>
                        <button type="button" class="toggle-senha">👁</button>
                    </div>
                    <small class="forca-senha">Força da senha: Sem senha</small>
                </div>

                <div class="form-group">
                    <label>Confirmar Senha *</label>
                            
                        
                    <div class="input-group">
                        <svg viewBox="0 0 24 24" class="input-icon">
                            <path d="M12 1C6.48 1 2 5.48 2 11v9h20v-9c0-5.52-4.48-10-10-10zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3z"></path>
                        </svg>
                        <input type="password" name="confirmar_senha" placeholder="Confirme sua senha" required>
                        <button type="button" class="toggle-senha">👁</button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nome *</label>
                        <div class="input-group">
                            <svg viewBox="0 0 24 24" class="input-icon">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M6 20c0-3.314 2.686-6 6-6s6 2.686 6 6"></path>
                            </svg>
                            <input type="text" name="nome" placeholder="Digite seu nome" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Sobrenome *</label>
                        <div class="input-group">
                            <svg viewBox="0 0 24 24" class="input-icon">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M6 20c0-3.314 2.686-6 6-6s6 2.686 6 6"></path>
                            </svg>
                            <input type="text" name="sobrenome" placeholder="Digite seu sobrenome" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="modal-button modal-button-primary" style="width: 100%;">
                    Cadastrar
                </button>
            </form>
        </div>
    </div>
</div>


<div class="carrinho-lateral-overlay" id="carrinhoLateralOverlay" onclick="fecharCarrinhoLateral()"></div>

<aside class="carrinho-lateral" id="carrinhoLateral">

<div class="carrinho-tela-padrao" id="carrinhoTelaPadrao"> 

    <button class="carrinho-lateral-fechar" onclick="fecharCarrinhoLateral()">
        &times;
    </button>

    <h2 class="carrinho-lateral-titulo">Carrinho</h2>
    <p class="carrinho-lateral-itens" id="carrinhoTotalItens">0 item</p>


    <div class="carrinho-lateral-lista" id="carrinhoLista"></div>

    <div class="carrinho-lateral-rodape">
        <div class="carrinho-lateral-subtotal">
            <span>Subtotal</span>
            <strong id="carrinhoSubtotal">R$ 0,00</strong>
        </div>

        <a href="carrinho.php" class="carrinho-ir">
            Ir para o carrinho
        </a>

        <button class="carrinho-continuar" onclick="fecharCarrinhoLateral()">
            Continuar comprando
        </button>
    </div>
    </div>



    <!-- TELA DE CONFIRMAÇÃO DE REMOÇÃO -->
    <div class="carrinho-confirmacao-remocao" id="carrinhoConfirmacaoRemocao">
        <button class="carrinho-voltar" onclick="cancelarRemocao()" aria-label="Voltar">
            &#8592;
        </button>


        <h2 class="carrinho-lateral-titulo">Carrinho</h2>
        <p class="carrinho-lateral-itens" id="confirmacaoTotalItens">0 item</p>



        <div class="confirmacao-remocao-centro">
            <div class="confirmacao-icone-remocao">
                <!-- ÍCONE NOVO, PARECIDO, MAS NÃO IGUAL -->
                <svg viewBox="0 0 220 220" aria-hidden="true">
                    <!-- fundo rosa -->
                    <circle cx="110" cy="110" r="58" fill="#f8c7d3"></circle>

                    <!-- cestinha -->
                    <rect x="62" y="92" width="96" height="64" rx="18" fill="#bfc6cc"></rect>
                    <rect x="60" y="88" width="100" height="20" rx="10" fill="#cfd5da"></rect>

                    <!-- alças -->
                    <path d="M85 98 L95 62" stroke="#555" stroke-width="8" stroke-linecap="round" fill="none"></path>
                    <path d="M135 98 L125 62" stroke="#555" stroke-width="8" stroke-linecap="round" fill="none"></path>

                    <!-- risquinhos da cesta -->
                    <rect x="84" y="112" width="10" height="32" rx="5" fill="#666"></rect>
                    <rect x="108" y="112" width="10" height="32" rx="5" fill="#666"></rect>
                    <rect x="132" y="112" width="10" height="32" rx="5" fill="#666"></rect>

                    <!-- selo laranja com traço -->
                    <circle cx="155" cy="145" r="24" fill="#e46d3a"></circle>
                    <rect x="143" y="142" width="24" height="6" rx="3" fill="#fff"></rect>
                </svg>
            </div>

            <h3>Remover produto</h3>
            <p>Tem certeza que deseja remover este item?</p>
        </div>

        <div class="confirmacao-botoes">
            <button class="carrinho-ir" onclick="confirmarRemocao()">Sim</button>
            <button class="carrinho-continuar" onclick="cancelarRemocao()">Não</button>
        </div>
    </div>

</aside>




<!-- ABA LATERAL DO PERFIL -->
<div class="perfil-overlay" id="perfilOverlay" onclick="fecharPerfilUsuario()"></div>

<aside class="perfil-lateral" id="perfilLateral">
    <button class="perfil-fechar" onclick="fecharPerfilUsuario()">
        &times;
    </button>

    <div class="perfil-cabecalho">
        <div class="perfil-logo-mini">
            +
        </div>

        <span>Boas-vindas!</span>

        <h2>
            <?= $clienteLogado ? escapar($clienteLogado['nome']) : 'Cliente' ?>
        </h2>
    </div>

    <div class="perfil-opcoes">

        <a href="gerenciar_perfil.php" class="perfil-opcao">
            <div class="perfil-opcao-icone">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"></path>
                    <path d="M17 3l4 4"></path>
                    <path d="M21 3l-4 4"></path>
                </svg>
            </div>

            <div>
                <strong>Gerenciar perfil</strong>
                <span>Nome, e-mail e dados da conta</span>
            </div>
        </a>

        <a href="trocar_senha.php" class="perfil-opcao">
            <div class="perfil-opcao-icone">
                <svg viewBox="0 0 24 24">
                    <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    <circle cx="12" cy="15" r="1.5"></circle>
                </svg>
            </div>

            <div>
                <strong>Trocar senha</strong>
                <span>Atualize sua senha de acesso</span>
            </div>
        </a>

        <a href="sair_usuario.php" class="perfil-opcao perfil-opcao-sair">
            <div class="perfil-opcao-icone">
                <svg viewBox="0 0 24 24">
                    <path d="M10 4H5v16h5"></path>
                    <path d="M14 8l4 4-4 4"></path>
                    <path d="M8 12h10"></path>
                </svg>
            </div>

            <div>
                <strong>Sair</strong>
                <span>Encerrar sessão do usuário</span>
            </div>
        </a>

    </div>
</aside>

<div class="atendimento-overlay" id="atendimentoOverlay" onclick="fecharAtendimento()"></div>

<aside class="atendimento-lateral" id="atendimentoLateral">
    <button class="atendimento-fechar" onclick="fecharAtendimento()">
        &times;
    </button>

    <div class="atendimento-cabecalho">
        <div class="atendimento-avatar">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 11.5A8.5 8.5 0 0 1 7.3 18.9L3 20L4.2 15.9A8.5 8.5 0 1 1 20 11.5Z"></path>
                <path d="M8.5 8.8C8.7 12 11.2 14.3 14.4 14.7"></path>
                <path d="M8.7 8.8L10.1 8.2L11 10L10.2 10.8"></path>
                <path d="M14.4 14.7L15.2 13.9L17 14.8L16.4 16.2"></path>
            </svg>
        </div>

        <div>
            <span>Atendimento</span>
            <h3>Drogaria PharmaPaz</h3>
            <p>Normalmente responde em alguns minutos</p>
        </div>
    </div>

    <div class="atendimento-mensagens" id="atendimentoMensagens">
        <div class="mensagem-atendente">
            Olá! Como podemos ajudar você?
        </div>

        <div class="mensagem-atendente">
            Tire dúvidas sobre produtos, disponibilidade ou formas de compra.
        </div>
    </div>

    <form class="atendimento-form" onsubmit="enviarMensagemAtendimento(event)">
        <input 
            type="text" 
            id="mensagemAtendimento" 
            placeholder="Digite sua dúvida..."
            autocomplete="off"
        >

        <button type="submit">
            Enviar
        </button>
    </form>
</aside>

<script>
    // Função para abrir o carrinho (com verificação de carrinho vazio)
    function abrirCarrinho(event) {
        event.preventDefault();
        
        // Faz requisição AJAX para verificar se o carrinho está vazio
        fetch('verifica_carrinho.php')
            .then(response => response.json())
            .then(data => {
                if (data.vazio) {
                    // Se vazio, mostra o modal
                    abrirModalCarrinho();
                } else {
                    // Se não vazio, redireciona para carrinho.php
                    window.location.href = 'carrinho.php';
                }
            })
            .catch(error => {
                console.error('Erro ao verificar carrinho:', error);
                // Em caso de erro, redireciona para carrinho.php como fallback
                window.location.href = 'carrinho.php';
            });
    }

    // Abre o modal do carrinho
    function abrirModalCarrinho() {
        const modal = document.getElementById('modalCarrinhoVazio');
        if (modal) {
            modal.classList.add('ativo');
        }
    }

    // Fecha o modal do carrinho
    function fecharModal() {
        const modal = document.getElementById('modalCarrinhoVazio');
        if (modal) {
            modal.classList.remove('ativo');
        }
    }

    // Abre o modal de Login/Cadastro
    function abrirLoginCadastro(event) {
        event.preventDefault();
        const modal = document.getElementById('modalLoginCadastro');
        if (modal) {
            modal.classList.add('ativo');
            voltarBoasVindas();
        }
    }

    // Fecha o modal de Login/Cadastro
    function fecharLoginCadastro() {
        const modal = document.getElementById('modalLoginCadastro');
        if (modal) {
            modal.classList.remove('ativo');
        }
    }

    // Mostra a tela de login
    function mostrarLogin() {
        document.getElementById('telaBoasVindas').style.display = 'none';
        document.getElementById('telaLogin').style.display = 'block';
        document.getElementById('telaCadastro').style.display = 'none';
    }

    // Mostra a tela de cadastro
    function mostrarCadastro() {
        document.getElementById('telaBoasVindas').style.display = 'none';
        document.getElementById('telaLogin').style.display = 'none';
        document.getElementById('telaCadastro').style.display = 'block';
    }

    // Volta para a tela de boas-vindas
    function voltarBoasVindas() {
        document.getElementById('telaBoasVindas').style.display = 'block';
        document.getElementById('telaLogin').style.display = 'none';
        document.getElementById('telaCadastro').style.display = 'none';
    }

    // Fecha o modal ao clicar fora dele
    document.addEventListener('DOMContentLoaded', function() {
        const modalCarrinho = document.getElementById('modalCarrinhoVazio');
        const modalLogin = document.getElementById('modalLoginCadastro');

        if (modalCarrinho) {
            modalCarrinho.addEventListener('click', function(event) {
                if (event.target === modalCarrinho) {
                    fecharModal();
                }
            });
        }

        if (modalLogin) {
            modalLogin.addEventListener('click', function(event) {
                if (event.target === modalLogin) {
                    fecharLoginCadastro();
                }
            });
        }

        // Toggle de visibilidade de senha
        document.querySelectorAll('.toggle-senha').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const input = this.parentElement.querySelector('input');
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = '👁';
                } else {
                    input.type = 'password';
                    this.textContent = '👁';
                }
            });
        });
    });

    // Funções para menu de categorias
    function abrirMenuCategorias() {
        const drawer = document.getElementById('menuDrawer');
        const overlay = document.getElementById('menuOverlay');
        if (drawer && overlay) {
            drawer.classList.add('ativo');
            overlay.classList.add('ativo');
            document.body.style.overflow = 'hidden';
        }
    }

    function fecharMenuCategorias() {
        const drawer = document.getElementById('menuDrawer');
        const overlay = document.getElementById('menuOverlay');
        if (drawer && overlay) {
            drawer.classList.remove('ativo');
            overlay.classList.remove('ativo');
            document.body.style.overflow = 'auto';
        }
    }



// TALVEZ MUDAR AQUI



    let codigoProdutoParaRemover = null;   


    function formatarMoeda(valor) {
    return Number(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function abrirCarrinhoLateral() {
    const lateral = document.getElementById('carrinhoLateral');
    const overlay = document.getElementById('carrinhoLateralOverlay');

    if (lateral && overlay) {
        lateral.classList.add('ativo');
        overlay.classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }
}

function fecharCarrinhoLateral() {
    const lateral = document.getElementById('carrinhoLateral');
    const overlay = document.getElementById('carrinhoLateralOverlay');

    if (lateral && overlay) {
        lateral.classList.remove('ativo');
        overlay.classList.remove('ativo');
        document.body.style.overflow = 'auto';
    }
}

function atualizarCarrinhoLateral(dados) {
    const lista = document.getElementById('carrinhoLista');
    const totalItens = document.getElementById('carrinhoTotalItens');
    const subtotal = document.getElementById('carrinhoSubtotal');

    if (!lista || !totalItens || !subtotal) {
        return;
    }

    lista.innerHTML = '';

    if (!dados.carrinho || dados.carrinho.length === 0) {
        lista.innerHTML = `
            <div class="carrinho-vazio-lateral">
                <div class="carrinho-vazio-icone">🛒</div>
                <strong>Sua cesta está vazia</strong>
                <p>Adicione produtos para continuar sua compra.</p>
            </div>
        `;

        totalItens.textContent = '0 item';
        subtotal.textContent = 'R$ 0,00';
        return;
    }

    totalItens.textContent = dados.totalItens === 1
        ? '1 item'
        : dados.totalItens + ' itens';

    subtotal.textContent = formatarMoeda(dados.subtotal);

    dados.carrinho.forEach(item => {
        const div = document.createElement('div');
        div.className = 'carrinho-lateral-produto';

        div.innerHTML = `
            <img src="${item.imagem}" alt="${item.nome}" class="carrinho-lateral-img">

            <div class="carrinho-lateral-info">
                <h3>${item.nome}</h3>
                <p>Código: ${item.codigo}</p>
                <strong>${formatarMoeda(item.preco)}</strong>

                <div class="carrinho-lateral-acoes">
                    <button onclick="alterarQuantidadeCarrinho('${item.codigo}', ${item.quantidade - 1})">−</button>
                    <span>${item.quantidade}</span>
                    <button onclick="alterarQuantidadeCarrinho('${item.codigo}', ${item.quantidade + 1})">+</button>

                    <button class="remover-item" onclick="mostrarConfirmacaoRemocao('${item.codigo}')">
                        Excluir 
                    </button>
                </div>
            </div>
        `;

        lista.appendChild(div);
    });
}

function adicionarProdutoCarrinho(botao) {
    const formData = new FormData();
    formData.append('acao', 'adicionar');
    formData.append('codigo', botao.dataset.codigo); 
    formData.append('nome', botao.dataset.nome);
    formData.append('preco', botao.dataset.preco);
    formData.append('imagem', botao.dataset.imagem);

    fetch('carrinho_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(dados => {
        if (dados.sucesso) {
            atualizarCarrinhoLateral(dados);
            abrirCarrinhoLateral();
        }
    })
    .catch(error => {
        console.error('Erro ao adicionar produto:', error);
    });
}

function alterarQuantidadeCarrinho(codigo, quantidade) {
    const formData = new FormData();
    formData.append('acao', 'alterar');
    formData.append('codigo', codigo);
    formData.append('quantidade', quantidade);

    fetch('carrinho_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(dados => {
        if (dados.sucesso) {
            atualizarCarrinhoLateral(dados);
        }
    });
}



function mostrarConfirmacaoRemocao(codigo) {
    codigoProdutoParaRemover = codigo;

    const telaPadrao = document.getElementById('carrinhoTelaPadrao');
    const telaConfirmacao = document.getElementById('carrinhoConfirmacaoRemocao');
    const totalItensAtual = document.getElementById('carrinhoTotalItens');
    const totalItensConfirmacao = document.getElementById('confirmacaoTotalItens');

    if (telaPadrao && telaConfirmacao) {
        telaPadrao.classList.add('oculto');
        telaConfirmacao.classList.add('ativo');
    }

    if (totalItensAtual && totalItensConfirmacao) {
        totalItensConfirmacao.textContent = totalItensAtual.textContent;
    }
}

function cancelarRemocao() {
    codigoProdutoParaRemover = null;

    const telaPadrao = document.getElementById('carrinhoTelaPadrao');
    const telaConfirmacao = document.getElementById('carrinhoConfirmacaoRemocao');

    if (telaPadrao && telaConfirmacao) {
        telaPadrao.classList.remove('oculto');
        telaConfirmacao.classList.remove('ativo');
    }
}

function confirmarRemocao() {
    if (!codigoProdutoParaRemover) {
        return;
    }

    const formData = new FormData();
    formData.append('acao', 'remover');
    formData.append('codigo', codigoProdutoParaRemover);

    fetch('carrinho_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(dados => {
        if (dados.sucesso) {
            atualizarCarrinhoLateral(dados);
            cancelarRemocao();
        }
    })
    .catch(error => {
        console.error('Erro ao remover produto:', error);   
    });
}



function removerItemCarrinho(codigo) {
    mostrarConfirmacaoRemocao(codigo);
}





document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.botao-comprar').forEach(botao => {
        botao.addEventListener('click', function (event) {
            event.preventDefault();
            adicionarProdutoCarrinho(this);
        });
    });
});





    // Fecha ao pressionar ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            fecharModal();
            fecharLoginCadastro();
            fecharMenuCategorias();
            fecharPerfilUsuario();
            fecharAtendimento();
        }
    });



    function abrirPerfilUsuario(event) {
    event.preventDefault();

    const perfil = document.getElementById('perfilLateral');
    const overlay = document.getElementById('perfilOverlay');

    if (perfil && overlay) {
        perfil.classList.add('ativo');
        overlay.classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }
}

function fecharPerfilUsuario() {
    const perfil = document.getElementById('perfilLateral');
    const overlay = document.getElementById('perfilOverlay');

    if (perfil && overlay) {
        perfil.classList.remove('ativo');
        overlay.classList.remove('ativo');
        document.body.style.overflow = 'auto';
    }
}

function abrirAtendimento() {
    const atendimento = document.getElementById('atendimentoLateral');
    const overlay = document.getElementById('atendimentoOverlay');

    if (atendimento && overlay) {
        atendimento.classList.add('ativo');
        overlay.classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }
}

function fecharAtendimento() {
    const atendimento = document.getElementById('atendimentoLateral');
    const overlay = document.getElementById('atendimentoOverlay');

    if (atendimento && overlay) {
        atendimento.classList.remove('ativo');
        overlay.classList.remove('ativo');
        document.body.style.overflow = 'auto';
    }
}

function enviarMensagemAtendimento(event) {
    event.preventDefault();

    const input = document.getElementById('mensagemAtendimento');
    const mensagens = document.getElementById('atendimentoMensagens');

    if (!input || !mensagens || input.value.trim() === '') {
        return;
    }

    const mensagemCliente = document.createElement('div');
    mensagemCliente.className = 'mensagem-cliente';
    mensagemCliente.textContent = input.value.trim();

    mensagens.appendChild(mensagemCliente);
    input.value = '';

    setTimeout(() => {
        const resposta = document.createElement('div');
        resposta.className = 'mensagem-atendente';
        resposta.textContent = 'Recebemos sua dúvida. Em breve nossa equipe responderá.';
        mensagens.appendChild(resposta);
        mensagens.scrollTop = mensagens.scrollHeight;
    }, 700);

    mensagens.scrollTop = mensagens.scrollHeight;
}



</script>

</body>
</html>