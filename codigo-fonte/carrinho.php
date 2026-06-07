<?php
session_start();

$carrinho = $_SESSION['carrinho'] ?? [];
$busca = '';

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
<body>

<header class="topo">
    <a href="produtos.php" class="logo">
        <img
            src="img/logo-pharmapaz.png"
            alt="Drogaria PharmaPaz"
            class="logo-img"
        >
    </a>

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

    <a href="#" class="cliente-acesso">
        <div class="cliente-icone-area">
            <svg class="cliente-icone" viewBox="0 0 48 48" aria-hidden="true">
                <circle cx="24" cy="14" r="10"></circle>
                <path d="M8 43V35C8 29.5 12.5 25 18 25H30C35.5 25 40 29.5 40 35V43Z"></path>
            </svg>
        </div>

        <div class="cliente-texto">
            <span>Bem-vindo,</span>
            <strong>Faça seu Login ou Cadastro</strong>
        </div>
    </a>

    <nav class="menu">
        <a href="produtos.php">Produtos</a>
        <a href="#">Ofertas</a>

        <a href="carrinho.php" class="menu-carrinho ativo" aria-label="Carrinho">
            <svg class="icone-carrinho" viewBox="0 0 48 48" aria-hidden="true">
                class="icone-carrinho" viewBox="0 0 48 48" aria-hidden="true">
            <path d="M10 16H38L34.5 31H13.5L10 16Z"></path>
            <path d="M16 16L20 10H28L32 16"></path>
            <line x1="18" y1="35" x2="18" y2="35"></line>
            <line x1="30" y1="35" x2="30" y2="35"></line>
            </svg>
        </a>
    </nav>
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
        <section class="lista-carrinho">
            <div class="resumo-topo-carrinho">
                <h2>Produtos adicionados</h2>
                <span class="badge-itens"><?= $totalItens ?> item(ns) no carrinho</span>
            </div>

            <table class="tabela-carrinho">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalGeral = 0;

                    foreach ($carrinho as $item):
                        $nome = $item['nome'] ?? 'Produto';
                        $quantidade = (int) ($item['quantidade'] ?? 1);
                        $preco = (float) ($item['preco'] ?? 0);
                        $subtotal = $quantidade * $preco;
                        $totalGeral += $subtotal;
                    ?>
                        <tr>
                            <td><?= escapar($nome) ?></td>
                            <td><?= $quantidade ?></td>
                            <td>R$ <?= number_format($preco, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-linha">
                Total: R$ <?= number_format($totalGeral, 2, ',', '.') ?>
            </div>
        </section>
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
    <p>Drogaria PharmaPaz • Projeto acadêmico integrado ao banco Firebird</p>
</footer>

</body>
</html>