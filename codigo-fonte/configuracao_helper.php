<?php
/**
 * Helper de Configuração do Sistema - Drogaria PharmaPaz
 * Gerencia nome da loja, logotipo e paleta de cores no banco Firebird
 */

if (!function_exists('obterConfiguracaoSistema')) {
    function obterConfiguracaoSistema(PDO $pdo): array
    {
        $padrao = [
            'ID'             => 1,
            'NOME_FARMACIA'  => 'Drogaria PharmaPaz',
            'LOGO_URL'       => 'imagens/logo-pharmapaz.png',
            'COR_PRIMARIA'   => '#008a73',
            'COR_SECUNDARIA' => '#009f9a',
            'COR_FUNDO'      => '#f4fbf9'
        ];

        try {
            $stmt = $pdo->query("SELECT FIRST 1 ID, NOME_FARMACIA, LOGO_URL, COR_PRIMARIA, COR_SECUNDARIA, COR_FUNDO FROM CONFIGURACAO_SISTEMA WHERE ID = 1");
            $dados = $stmt->fetch();
            if ($dados) {
                return [
                    'ID'             => 1,
                    'NOME_FARMACIA'  => trim($dados['NOME_FARMACIA'] ?? '') ?: $padrao['NOME_FARMACIA'],
                    'LOGO_URL'       => trim($dados['LOGO_URL'] ?? '') ?: $padrao['LOGO_URL'],
                    'COR_PRIMARIA'   => trim($dados['COR_PRIMARIA'] ?? '') ?: $padrao['COR_PRIMARIA'],
                    'COR_SECUNDARIA' => trim($dados['COR_SECUNDARIA'] ?? '') ?: $padrao['COR_SECUNDARIA'],
                    'COR_FUNDO'      => trim($dados['COR_FUNDO'] ?? '') ?: $padrao['COR_FUNDO']
                ];
            }
        } catch (Exception $e) {
            // Em caso de indisponibilidade retorna valores padrão
        }

        return $padrao;
    }
}

