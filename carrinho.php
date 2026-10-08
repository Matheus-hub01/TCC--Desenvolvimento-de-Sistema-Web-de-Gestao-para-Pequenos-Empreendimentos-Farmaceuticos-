<?php
session_start();

$carrinho = $_SESSION['carrinho'] ?? [];
$busca = '';
$clienteLogado = $_SESSION['cliente_logado'] ?? null;

$totalItens = 0;

foreach ($carrinho as $item) {
    $totalItens += (int) ($item['quantidade'] ?? 1);
}

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho | Drogaria PharmaPaz</title>
    <link rel="stylesheet" href="css/style.css">

    <style>

        body.pagina-carrinho-body {
    background: #f4f7f6;
}

.pagina-carrinho-body .topo-container {
    width: min(100%, 1500px);
    margin: 0 auto;
    padding: 10px 34px;
    min-height: 86px;

    display: flex;
    align-items: center;

    gap: 24px;
}

.pagina-carrinho-body .topo-esquerda {
    display: flex;
    align-items: center;

    flex: 0 0 auto;
}

.pagina-carrinho-body .logo-img {
    width: 104px;
    clip-path: none;
}

.pagina-carrinho-body .busca-topo {
    flex: 1 1 560px;

    max-width: 720px;
    min-width: 260px;

    margin: 0;
}

.pagina-carrinho-body .topo-direita {
    margin-left: auto;

    display: flex;
    align-items: center;

    gap: 18px;

    flex: 0 0 auto;
}


/* remove alguns deslocamentos antigos */
.pagina-carrinho-body .cliente-acesso,
.pagina-carrinho-body .icone-carrinho,
.pagina-carrinho-body .topo .menu a:nth-child(1) {
    transform: none;
}


.pagina-carrinho-body .cliente-acesso {
    gap: 10px;
}


.pagina-carrinho-body .cliente-icone {
    width: 34px;
    height: 34px;
}


.pagina-carrinho-body .cliente-texto strong {
    max-width: 210px;

    overflow: hidden;

    text-overflow: ellipsis;
}


.link-produtos-topo {
    color: #495057;

    text-decoration: none;

    font-weight: 700;

    font-size: 15px;

    padding: 10px 12px;

    border-radius: 10px;

    transition: 0.2s ease;
}


.link-produtos-topo:hover {
    color: var(--verde-principal);

    background: #eef8f5;
}


.carrinho-atalho {
    position: relative;
}


.contador-carrinho-topo {
    position: absolute;

    top: -5px;
    right: -7px;

    min-width: 20px;
    height: 20px;

    padding: 0 5px;

    border-radius: 999px;

    background: #ef3340;

    color: white;

    font-size: 11px;

    font-weight: 800;

    display: flex;

    align-items: center;
    justify-content: center;

    border: 2px solid white;
}

        .pagina-carrinho {
            max-width: 1150px;
            margin: 0 auto;
            padding: 40px 20px 70px;
        }

        .cabecalho-carrinho {
            margin-bottom: 28px;
        }

        .cabecalho-carrinho h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .cabecalho-carrinho p {
            color: #6b7280;
            font-size: 16px;
        }

        .carrinho-vazio-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
            padding: 60px 35px;
            text-align: center;
            border: 1px solid #edf1f2;
        }

        .ilustracao-vazio {
            width: 125px;
            height: 125px;
            border-radius: 50%;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #e7f7f6, #f1fbfa);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .ilustracao-vazio svg {
            width: 62px;
            height: 62px;
            fill: none;
            stroke: var(--verde-principal);
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .selo-farmacia {
            position: absolute;
            right: 6px;
            top: 6px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #ef3340;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(239, 51, 64, 0.22);
        }

        .selo-farmacia::before,
        .selo-farmacia::after {
            content: "";
            position: absolute;
            background: #ffffff;
            border-radius: 2px;
        }

        .selo-farmacia::before {
            width: 16px;
            height: 4px;
        }

        .selo-farmacia::after {
            width: 4px;
            height: 16px;
        }

        .titulo-vazio {
            font-size: 34px;
            color: #1f2937;
            margin-bottom: 14px;
        }

        .texto-vazio {
            max-width: 740px;
            margin: 0 auto 28px;
            font-size: 18px;
            line-height: 1.6;
            color: #5f6b76;
        }

        .acoes-vazio {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .botao-vazio {
            min-width: 230px;
            height: 52px;
            padding: 0 24px;
            border-radius: 999px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            transition: 0.2s;
        }

        .botao-principal-vazio {
            background: linear-gradient(135deg, #009f9a, #007e79);
            color: #ffffff;
            box-shadow: 0 8px 22px rgba(0, 143, 137, 0.22);
        }

        .botao-principal-vazio:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
        }

        .botao-secundario-vazio {
            background: #eef7f6;
            color: var(--verde-principal);
            border: 1px solid #dbeceb;
        }

        .botao-secundario-vazio:hover {
            background: #e4f4f2;
        }

        .lista-carrinho {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
            padding: 30px;
            border: 1px solid #edf1f2;
        }

        .resumo-topo-carrinho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .resumo-topo-carrinho h2 {
            font-size: 26px;
            color: #1f2937;
        }

        .badge-itens {
            background: #eaf8f6;
            color: var(--verde-principal);
            font-weight: 700;
            padding: 10px 16px;
            border-radius: 999px;
            font-size: 14px;
        }

        .tabela-carrinho {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-carrinho th,
        .tabela-carrinho td {
            padding: 16px 10px;
            border-bottom: 1px solid #edf1f2;
            text-align: left;
        }

        .tabela-carrinho th {
            color: #5f6b76;
            font-size: 14px;
        }

        .tabela-carrinho td {
            color: #1f2937;
            font-size: 15px;
        }

        .total-linha {
            text-align: right;
            margin-top: 22px;
            font-size: 22px;
            font-weight: 700;
            color: var(--verde-principal);
        }

        .menu-carrinho.ativo {
            background: #eaf8f6;
            border-radius: 10px;
        }

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

        @media (max-width: 900px) {
            .titulo-vazio {
                font-size: 28px;
            }

            .texto-vazio {
                font-size: 16px;
            }

            .lista-carrinho {
                overflow-x: auto;
            }

            .tabela-carrinho {
                min-width: 650px;
            }

            .modal-content {
                padding: 40px 30px;
                border-radius: 28px;
            }

            .modal-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body class="pagina-carrinho-body">

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
                    <line
                        x1="16.5"
                        y1="16.5"
                        x2="21"
                        y2="21"
                    ></line>
                </svg>

            </button>

        </form>

        <div class="topo-direita">

            <a href="produtos.php" class="link-produtos-topo">
                Produtos
            </a>

            <a
                href="produtos.php"
                class="cliente-acesso"
                aria-label="Área do cliente"
            >

                <svg
                    class="cliente-icone"
                    viewBox="0 0 48 48"
                    aria-hidden="true"
                >
                    <circle cx="24" cy="14" r="10"></circle>

                    <path
                        d="M8 43V35C8 29.5 12.5 25 18 25H30C35.5 25 40 29.5 40 35V43Z"
                    ></path>
                </svg>

                <div class="cliente-texto">

                    <?php if ($clienteLogado): ?>

                        <span>Perfil</span>

                        <strong>
                            <?= escapar(
                                (string) ($clienteLogado['nome'] ?? 'Cliente')
                            ) ?>
                        </strong>

                    <?php else: ?>

                        <span>Bem-vindo(a),</span>

                        <strong>
                            Login ou Cadastro
                        </strong>

                    </section>

                    <?php endif; ?>

                </div>

            </a>

            <a
                href="carrinho.php"
                class="menu-carrinho carrinho-atalho ativo"
                aria-label="Carrinho"
            >

                <svg
                    class="icone-carrinho"
                    viewBox="0 0 48 48"
                    aria-hidden="true"
                >

                    <path
                        d="M10 16H38L34.5 31H13.5L10 16Z"
                    ></path>

                    <path
                        d="M16 16L20 10H28L32 16"
                    ></path>

                    <circle
                        cx="18"
                        cy="36"
                        r="1.5"
                    ></circle>

                    <circle
                        cx="30"
                        cy="36"
                        r="1.5"
                    ></circle>

                </svg>

                <span
                    class="contador-carrinho-topo"
                    id="contadorCarrinhoTopo"
                >
                    <?= $totalItens ?>
                </span>

            </a>

        </div>

    </div>
</header>

<main class="pagina-carrinho">
    <section class="cabecalho-carrinho">
        <h1>Meu carrinho</h1>
        <p>Acompanhe seus produtos selecionados e finalize sua compra com praticidade.</p>
    </section>

    <?php if (empty($carrinho)): ?>
        <section class="carrinho-vazio-card">
            <div class="ilustracao-vazio">
                <svg viewBox="0 0 64 64" aria-hidden="true">
                    <path d="M14 24H50L45.5 43H18.5L14 24Z"></path>
                    <path d="M22 24L27 16H37L42 24"></path>
                    <circle cx="24" cy="48" r="1.8"></circle>
                    <circle cx="40" cy="48" r="1.8"></circle>
                </svg>
                <div class="selo-farmacia"></div>
            </div>

            <h2 class="titulo-vazio">Seu carrinho está vazio</h2>

            <p class="texto-vazio">
                Adicione medicamentos, vitaminas e itens de cuidado pessoal para continuar sua compra.
                Explore o catálogo da PharmaPaz e encontre o que você precisa.
            </p>

            <div class="acoes-vazio">
                <a href="produtos.php" class="botao-vazio botao-principal-vazio">
                    Escolher produtos
                </a>

                <a href="produtos.php" class="botao-vazio botao-secundario-vazio">
                    Continuar navegando
                </a>
            </div>
        </section>


    <?php else: ?>
        <section class="lista-carrinho" id="listaCarrinho">

    <div class="resumo-topo-carrinho">

        <h2>
            Produtos adicionados
        </h2>

        <span
            class="badge-itens"
            id="badgeItens"
        >
            <?= $totalItens ?> item(ns) no carrinho
        </span>

    </div>


    <div class="tabela-carrinho-wrapper">

        <table class="tabela-carrinho">

            <thead>

                <tr>

                    <th>
                        Produto
                    </th>

                    <th>
                        Quantidade
                    </th>

                    <th>
                        Preço
                    </th>

                    <th>
                        Subtotal
                    </th>

                    <th aria-label="Ações"></th>

                </tr>

            </thead>


            <tbody id="corpoCarrinho">

                <?php

                $totalGeral = 0;

                foreach ($carrinho as $chave => $item):

                    $codigo =
                        (string) (
                            $item['codigo']
                            ?? $chave
                        );

                    $nome =
                        (string) (
                            $item['nome']
                            ?? 'Produto'
                        );

                    $imagem =
                        (string) (
                            $item['imagem']
                            ?? 'img/produtos/sem-imagem.png'
                        );

                    $quantidade =
                        max(
                            1,
                            (int) (
                                $item['quantidade']
                                ?? 1
                            )
                        );

                    $preco =
                        (float) (
                            $item['preco']
                            ?? 0
                        );

                    $subtotal =
                        $quantidade
                        * $preco;

                    $totalGeral +=
                        $subtotal;

                ?>

                    <tr
                        data-codigo="<?= escapar($codigo) ?>"
                    >

                        <td>

                            <div class="produto-carrinho-info">

                                <img
                                    src="<?= escapar($imagem) ?>"
                                    alt="<?= escapar($nome) ?>"
                                    class="produto-carrinho-img"
                                    onerror="this.style.display='none'"
                                >

                                <div>

                                    <div class="produto-carrinho-nome">

                                        <?= escapar($nome) ?>

                                    </div>


                                    <div class="produto-carrinho-codigo">

                                        Código:

                                        <?= escapar($codigo) ?>

                                    </div>

                                </div>

                            </div>

                        </td>

    <td>

    <div class="controle-quantidade">

        <button
            type="button"
            class="botao-quantidade botao-menos"

            onclick='alterarQuantidade(
                <?= json_encode(
                    $codigo,
                    JSON_HEX_APOS | JSON_HEX_QUOT
                ) ?>,

                <?= $quantidade - 1 ?>
            )'

            <?= $quantidade <= 1
                ? 'disabled'
                : ''
            ?>
        >
            −
        </button>


        <span class="quantidade-valor">

            <?= $quantidade ?>

        </span>


        <button
            type="button"
            class="botao-quantidade botao-mais"

            onclick='alterarQuantidade(
                <?= json_encode(
                    $codigo,
                    JSON_HEX_APOS | JSON_HEX_QUOT
                ) ?>,

                <?= $quantidade + 1 ?>
            )'
        >
            +
        </button>

    </div>

</td>

<td class="preco-unitario">

    R$
    <?= number_format(
        $preco,
        2,
        ',',
        '.'
    ) ?>

</td>


<td class="subtotal-item">

    R$
    <?= number_format(
        $subtotal,
        2,
        ',',
        '.'
    ) ?>

</td>

<td>

    <button
        type="button"
        class="botao-lixeira"

        title="Excluir item"

        onclick='removerItem(
            <?= json_encode(
                $codigo,
                JSON_HEX_APOS | JSON_HEX_QUOT
            ) ?>
        )'
    >

        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
        >

            <path d="M3 6h18"></path>

            <path d="M8 6V4h8v2"></path>

            <path d="M19 6l-1 14H6L5 6"></path>

            <path d="M10 11v5"></path>

            <path d="M14 11v5"></path>

        </svg>

    </button>

</td>

</tr>

<?php endforeach; ?>

                </tbody>
         </table>

</div>

<div class="rodape-resumo-carrinho">  

    <div class="acoes-final-carrinho">

        <a href="produtos.php" class="botao-continuar">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M15 6L9 12L15 18"></path>
    </svg>

    <span>Continuar comprando</span>
</a>

    </div>

    <div class="bloco-finalizacao">

        <div class="total-linha">
            <span>Total do carrinho</span>

            <strong id="totalCarrinho">
                R$ <?= number_format($totalGeral, 2, ',', '.') ?>
            </strong>
        </div>

        <?php if ($clienteLogado): ?>

    <a href="finalizar_pedido.php" class="botao-finalizar">
        <span>Finalizar compra</span>

        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 12H19"></path>
            <path d="M13 6L19 12L13 18"></path>
        </svg>
    </a>

<?php else: ?>

    <a
        href="#"
        class="botao-finalizar"
        onclick="abrirLoginCadastro(event)"
    >
        <span>Finalizar compra</span>

        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 12H19"></path>
            <path d="M13 6L19 12L13 18"></path>
        </svg>
    </a>

<?php endif; ?>

    </div>

</div>

        
    <?php endif; ?>
</main>

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

        <a href="produtos.php" class="modal-button">Continuar comprando</a>
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

    <input
        type="hidden"
        name="origem"
        value="finalizar_pedido"
    >

               
            
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
                <input
                  type="hidden"
                     name="origem"
                     value="carrinho"
                 >
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

<script>
    // Abre o modal se o carrinho estiver vazio
    function abrirModalCarrinho() {
        const modal = document.getElementById('modalCarrinhoVazio');
        if (modal) {
            modal.classList.add('ativo');
        }
    }

    // Fecha o modal
    function fecharModal() {
        const modal = document.getElementById('modalCarrinhoVazio');
        if (modal) {
            modal.classList.remove('ativo');
        }
    }

    // Fecha o modal ao clicar fora dele
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalCarrinhoVazio');
        if (modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    fecharModal();
                }
            });
        }

        // Abre o modal automaticamente se carrinho estiver vazio
        <?php if (empty($carrinho)): ?>
            abrirModalCarrinho();
        <?php endif; ?>
    });

    // Fecha ao pressionar ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            fecharModal();
        }
    });
</script>

<footer class="rodape">
    <p>Drogaria PharmaPaz</p>
</footer>


<script>

    function abrirLoginCadastro(event) {

    if (event) {
        event.preventDefault();
    }

    const modal =
        document.getElementById('modalLoginCadastro');

    if (modal) {
        modal.classList.add('ativo');
        voltarBoasVindas();
    }

}


function fecharLoginCadastro() {

    const modal =
        document.getElementById('modalLoginCadastro');

    if (modal) {
        modal.classList.remove('ativo');
    }

}


function mostrarLogin() {

    document.getElementById(
        'telaBoasVindas'
    ).style.display = 'none';

    document.getElementById(
        'telaLogin'
    ).style.display = 'block';

    document.getElementById(
        'telaCadastro'
    ).style.display = 'none';

}


function mostrarCadastro() {

    document.getElementById(
        'telaBoasVindas'
    ).style.display = 'none';

    document.getElementById(
        'telaLogin'
    ).style.display = 'none';

    document.getElementById(
        'telaCadastro'
    ).style.display = 'block';

}


function voltarBoasVindas() {

    document.getElementById(
        'telaBoasVindas'
    ).style.display = 'block';

    document.getElementById(
        'telaLogin'
    ).style.display = 'none';

    document.getElementById(
        'telaCadastro'
    ).style.display = 'none';

}

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modal =
            document.getElementById(
                'modalLoginCadastro'
            );

        if (modal) {

            modal.addEventListener(
                'click',
                function (event) {

                    if (event.target === modal) {
                        fecharLoginCadastro();
                    }

                }
            );

        }


        const parametros =
            new URLSearchParams(
                window.location.search
            );


        if (
            parametros.get('login')
            ===
            'necessario'
        ) {

            const modal =
                document.getElementById(
                    'modalLoginCadastro'
                );

            if (modal) {
                modal.classList.add('ativo');
                voltarBoasVindas();
            }

        }


        if (
            parametros.get('abrir')
            ===
            'login'
        ) {

            const modal =
                document.getElementById(
                    'modalLoginCadastro'
                );

            if (modal) {
                modal.classList.add('ativo');
                mostrarLogin();
            }

        }

    }
);

function formatarMoeda(valor) {

    return Number(valor).toLocaleString(
        'pt-BR',
        {
            style: 'currency',
            currency: 'BRL'
        }
    );

}


function formatarMoeda(valor) {

    return Number(valor).toLocaleString(
        'pt-BR',
        {
            style: 'currency',
            currency: 'BRL'
        }
    );

}

async function enviarAcaoCarrinho(
    acao,
    codigo,
    quantidade = null
) {

    const formData =
        new FormData();


    formData.append(
        'acao',
        acao
    );


    formData.append(
        'codigo',
        codigo
    );


    if (quantidade !== null) {

        formData.append(
            'quantidade',
            quantidade
        );

    }


    const response =
        await fetch(
            'carrinho_ajax.php',
            {
                method: 'POST',
                body: formData
            }
        );


    if (!response.ok) {

        throw new Error(
            'Não foi possível atualizar o carrinho.'
        );

    }


    const dados =
        await response.json();


    if (!dados.sucesso) {

        throw new Error(
            dados.mensagem
            ||
            'Não foi possível atualizar o carrinho.'
        );

    }


    return dados;
}

function atualizarResumoCarrinho(dados) {

    const badge =
        document.getElementById(
            'badgeItens'
        );


    const contadorTopo =
        document.getElementById(
            'contadorCarrinhoTopo'
        );


    const total =
        document.getElementById(
            'totalCarrinho'
        );


    if (badge) {

        badge.textContent =
            `${dados.totalItens} item(ns) no carrinho`;

    }


    if (contadorTopo) {

        contadorTopo.textContent =
            dados.totalItens;

    }


    if (total) {

        total.textContent =
            formatarMoeda(
                dados.subtotal
            );

    }

}

function encontrarItem(
    dados,
    codigo
) {

    return (
        dados.carrinho || []
    ).find(
        item =>
            String(item.codigo)
            ===
            String(codigo)
    );

}



async function alterarQuantidade(
    codigo,
    novaQuantidade
) {

    if (novaQuantidade < 1) {
        return;
    }


    const linha =
        document.querySelector(
            `tr[data-codigo="${
                CSS.escape(
                    String(codigo)
                )
            }"]`
        );


    try {

        if (linha) {

            linha.classList.add(
                'linha-removendo'
            );

        }


        const dados =
            await enviarAcaoCarrinho(
                'alterar',
                codigo,
                novaQuantidade
            );


        const itemAtualizado =
            encontrarItem(
                dados,
                codigo
            );


        if (!itemAtualizado) {

            window.location.reload();

            return;
        }


        if (linha) {

            const quantidade =
                Number(
                    itemAtualizado.quantidade
                );


            const quantidadeTexto =
                linha.querySelector(
                    '.quantidade-valor'
                );


            const botaoMenos =
                linha.querySelector(
                    '.botao-menos'
                );


            const botaoMais =
                linha.querySelector(
                    '.botao-mais'
                );


            const subtotal =
                linha.querySelector(
                    '.subtotal-item'
                );


            if (quantidadeTexto) {

                quantidadeTexto.textContent =
                    quantidade;

            }


            if (botaoMenos) {

                botaoMenos.disabled =
                    quantidade <= 1;


                botaoMenos.setAttribute(
                    'onclick',

                    `alterarQuantidade(
                        ${JSON.stringify(
                            String(codigo)
                        )},
                        ${quantidade - 1}
                    )`
                );

            }


            if (botaoMais) {

                botaoMais.setAttribute(
                    'onclick',

                    `alterarQuantidade(
                        ${JSON.stringify(
                            String(codigo)
                        )},
                        ${quantidade + 1}
                    )`
                );

            }


            if (subtotal) {

                subtotal.textContent =
                    formatarMoeda(
                        Number(
                            itemAtualizado.preco
                        )
                        *
                        quantidade
                    );

            }

        }


        atualizarResumoCarrinho(
            dados
        );

    }

    catch (erro) {

        alert(
            erro.message
            ||
            'Erro ao atualizar a quantidade.'
        );

    }

    finally {

        if (linha) {

            linha.classList.remove(
                'linha-removendo'
            );

        }

    }

}

async function removerItem(codigo) {

    const linha =
        document.querySelector(
            `tr[data-codigo="${
                CSS.escape(
                    String(codigo)
                )
            }"]`
        );


    const nome =
        linha
            ?.querySelector(
                '.produto-carrinho-nome'
            )
            ?.textContent
            ?.trim()

        || 'este produto';


    const confirmar =
        window.confirm(
            `Deseja remover ${nome} do carrinho?`
        );


    if (!confirmar) {

        return;

    }


    try {

        if (linha) {

            linha.classList.add(
                'linha-removendo'
            );

        }


        const dados =
            await enviarAcaoCarrinho(
                'remover',
                codigo
            );


        if (
            !dados.carrinho
            ||
            dados.carrinho.length === 0
        ) {

            window.location.reload();

            return;

        }


        if (linha) {

            linha.remove();

        }


        atualizarResumoCarrinho(
            dados
        );

    }

    catch (erro) {

        if (linha) {

            linha.classList.remove(
                'linha-removendo'
            );

        }


        alert(
            erro.message
            ||
            'Erro ao remover o produto.'
        );

    }

}

</script>


</body>
</html>