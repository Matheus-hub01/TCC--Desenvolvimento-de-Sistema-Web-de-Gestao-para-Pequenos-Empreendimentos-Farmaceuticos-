<?php

session_start();

$pedido = $_SESSION['ultimo_pedido'] ?? null;

if (!$pedido) {
    header('Location: produtos.php');
    exit;
}

function escaparPedido(string $valor): string
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Pedido confirmado | PharmaPaz
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body class="pagina-confirmacao-pedido">

<main class="confirmacao-pedido-container">

    <section class="confirmacao-pedido-card">

        <div class="confirmacao-sucesso">

            <div class="confirmacao-check">
                ✓
            </div>

            <h1>
                Pedido realizado!
            </h1>

            <p>
                Seu pedido foi enviado para a Drogaria PharmaPaz.
            </p>

        </div>


        <div class="numero-pedido-box">

            <span>
                Número do pedido
            </span>

            <strong>
                #<?= escaparPedido(
                    (string) $pedido['numero']
                ) ?>
            </strong>

        </div>


        <div class="dados-pedido">

            <div>
                <span>Cliente</span>

                <strong>
                    <?= escaparPedido(
                        (string) $pedido['cliente']
                    ) ?>
                </strong>
            </div>


            <div>
                <span>Data</span>

                <strong>
                    <?= escaparPedido(
                        (string) $pedido['data']
                    ) ?>
                </strong>
            </div>

        </div>

                <div class="status-pedido">

            <div class="status-bolinha"></div>

            <div>

                <span>
                    Status do pedido
                </span>

                <strong>
                    <?= escaparPedido(
                        (string) $pedido['status']
                    ) ?>
                </strong>

            </div>

        </div>

                <div class="confirmacao-itens">

            <h2>
                Itens do pedido
            </h2>


            <?php foreach ($pedido['itens'] as $item): ?>

                <div class="confirmacao-item">

                    <div class="confirmacao-item-info">

                        <strong>
                            <?= escaparPedido(
                                (string) $item['nome']
                            ) ?>
                        </strong>

                        <small>
                            Código:
                            <?= escaparPedido(
                                (string) $item['codigo']
                            ) ?>
                        </small>

                    </div>


                    <div class="confirmacao-item-qtd">

                        <?= (int) $item['quantidade'] ?>x

                    </div>


                    <div class="confirmacao-item-preco">

                        R$
                        <?= number_format(
                            (float) $item['subtotal'],
                            2,
                            ',',
                            '.'
                        ) ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

                <div class="confirmacao-total">

            <span>
                Total
            </span>

            <strong>
                R$
                <?= number_format(
                    (float) $pedido['total'],
                    2,
                    ',',
                    '.'
                ) ?>
            </strong>

        </div>

                <div class="retirada-pedido">

            <h3>
                Retirada na farmácia
            </h3>

            <p>
                Seu pedido será preparado pela nossa equipe.
                O pagamento será realizado no momento da retirada.
            </p>

        </div>

                <div class="qr-pedido">

            <div class="qr-placeholder">
                QR
            </div>

            <div>
                <strong>
                    Pagamento na retirada
                </strong>

                <p>
                    O QR Code de pagamento será disponibilizado
                    quando o pedido estiver pronto.
                </p>
            </div>

        </div>

                <a
            href="produtos.php"
            class="voltar-produtos-confirmacao"
        >
            Voltar para os produtos
        </a>

    </section>

</main>

</body>

</html>