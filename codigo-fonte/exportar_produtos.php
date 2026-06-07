<?php
require_once 'conexao.php';

$sql = "
    SELECT
        P.DESCRICAOVENDA AS NOME_PRODUTO,
        COALESCE(G.DESCRICAO, 'Outros') AS CATEGORIA
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
        P.DESCRICAOVENDA,
        G.DESCRICAO
    HAVING SUM(E.QTDEESTOQUE) > 0
    ORDER BY
        G.DESCRICAO,
        P.DESCRICAOVENDA
";

$produtos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="produtos_para_classificar.csv"');

$arquivo = fopen('php://output', 'w');

/* Faz o Excel reconhecer acentos corretamente */
fprintf($arquivo, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($arquivo, ['Produto', 'Categoria'], ';');

foreach ($produtos as $produto) {
    fputcsv($arquivo, [
        trim($produto['NOME_PRODUTO']),
        trim($produto['CATEGORIA'])
    ], ';');
}

fclose($arquivo);
exit;
?>