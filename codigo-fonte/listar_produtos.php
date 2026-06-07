<?php
require_once 'conexao.php';

$sql = "
    SELECT
        P.CODPRODUTO,
        P.DESCRICAOVENDA AS NOME_PRODUTO,
        COALESCE(G.DESCRICAO, 'Outros') AS CATEGORIA,
        SUM(E.QTDEESTOQUE) AS ESTOQUE
    FROM PRODUTO P
    INNER JOIN ESTOQUE E
        ON E.CODPRODUTO = P.CODPRODUTO
    LEFT JOIN GRUPOPRODUTO G
        ON G.CODGRUPOPRODUTO = P.CODGRUPOPRODUTO
    WHERE
        COALESCE(P.STDESATIVADO, '0') <> '1'
        AND P.PRECOUNITARIOVENDA > 0
        AND E.CODORGANIZACAO = '001'
    GROUP BY
        P.CODPRODUTO,
        P.DESCRICAOVENDA,
        G.DESCRICAO
    HAVING SUM(E.QTDEESTOQUE) > 0
    ORDER BY
        G.DESCRICAO,
        P.DESCRICAOVENDA
";

$produtos = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Produtos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #008a73;
            color: white;
        }
    </style>
</head>
<body>

<h1>Produtos cadastrados no banco</h1>

<p>Total encontrado: <?= count($produtos) ?></p>

<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Produto</th>
            <th>Categoria</th>
            <th>Estoque</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($produtos as $produto): ?>
            <tr>
                <td><?= htmlspecialchars($produto['CODPRODUTO']) ?></td>
                <td><?= htmlspecialchars($produto['NOME_PRODUTO']) ?></td>
                <td><?= htmlspecialchars($produto['CATEGORIA']) ?></td>
                <td><?= htmlspecialchars($produto['ESTOQUE']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>