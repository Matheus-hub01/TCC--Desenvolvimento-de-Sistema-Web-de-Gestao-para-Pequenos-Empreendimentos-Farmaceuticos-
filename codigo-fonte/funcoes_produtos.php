<?php
/**
 * Funções compartilhadas para produtos e imagens
 * Drogaria PharmaPaz - TCC Gestão Farmacêutica
 */

if (!function_exists('imagemProduto')) {
    function imagemProduto($codigoProduto, $codigoBarra = ''): string
    {
        $pastasServidor = [
            __DIR__ . '/imagens/produtos/',
            __DIR__ . '/img/produtos/',
            dirname(__DIR__) . '/imagens/produtos/'
        ];
        $pastaSite = 'imagens/produtos/';

        $extensoes = ['webp', 'png', 'jpg', 'jpeg', 'svg'];

        $possiveisNomes = [];

        if (!empty($codigoBarra)) {
            $cb = trim((string) $codigoBarra);
            $possiveisNomes[] = $cb;
            $possiveisNomes[] = ltrim($cb, '0');
            $possiveisNomes[] = str_pad(ltrim($cb, '0'), 10, '0', STR_PAD_LEFT);
        }

        if (!empty($codigoProduto)) {
            $cp = trim((string) $codigoProduto);
            $possiveisNomes[] = $cp;
            $possiveisNomes[] = ltrim($cp, '0');
            $possiveisNomes[] = str_pad(ltrim($cp, '0'), 10, '0', STR_PAD_LEFT);
        }

        $possiveisNomes = array_unique(array_filter($possiveisNomes));

        foreach ($possiveisNomes as $nome) {
            foreach ($extensoes as $extensao) {
                foreach ($pastasServidor as $pasta) {
                    $arquivoServidor = $pasta . $nome . '.' . $extensao;
                    if (file_exists($arquivoServidor)) {
                        return $pastaSite . $nome . '.' . $extensao;
                    }
                }
            }
        }

        return 'imagens/produtos/sem-imagem.svg';
    }
}

if (!function_exists('formatarNomeProduto')) {
    function formatarNomeProduto(string $nome, string $codigoProduto = '', string $codigoBarra = ''): string
    {
        $nome = trim($nome);
        $codigoProduto = trim((string) $codigoProduto);
        $codigoBarra = trim((string) $codigoBarra);

        // Remove código de barras no início do nome se houver
        if ($codigoBarra !== '' && strpos($nome, $codigoBarra) === 0) {
            $nome = substr($nome, strlen($codigoBarra));
        }

        // Remove código do produto no início do nome se houver
        if ($codigoProduto !== '' && strpos($nome, $codigoProduto) === 0) {
            $nome = substr($nome, strlen($codigoProduto));
        }

        // Remove códigos no formato :NUMERO no final do nome (ex: DIPIRONA...:8428)
        $nome = preg_replace('/:\d+\s*$/', '', $nome);

        // Remove números longos no começo: 7896025Bloco...
        $nome = preg_replace('/^\s*\d{5,}\s*/u', '', $nome);

        // Corrige casos como 0Oleo -> Oleo
        $nome = preg_replace('/^\s*0+(?=[A-Za-zÀ-ÿ])/u', '', $nome);

        // Coloca espaço entre número e letra quando vier grudado
        $nome = preg_replace('/(?<=[A-Za-zÀ-ÿ])(?=\d)/u', ' ', $nome);
        $nome = preg_replace('/(?<=\d)(?=[A-Za-zÀ-ÿ])/u', ' ', $nome);

        // Padroniza espaços ao redor de +
        $nome = preg_replace('/\s*\+\s*/u', ' + ', $nome);

        // Deixa o texto em formato legível
        $nome = mb_strtolower($nome, 'UTF-8');
        $nome = mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');

        // Abreviações farmacêuticas comuns
        $nome = preg_replace('/\bC\/\s*/iu', 'com ', $nome);
        $nome = preg_replace('/\bS\/\s*/iu', 'sem ', $nome);
        $nome = preg_replace('/\bCx\.?\s*(?=\d)/iu', '', $nome);
        $nome = preg_replace('/\bCx\.?\b/iu', 'caixa', $nome);
        $nome = preg_replace('/(\d+)\s*(Cpr|Comp|Comps|Cp)\b\.?/iu', '$1 comprimidos', $nome);
        $nome = preg_replace('/\b(Cpr|Comp|Comps|Cp)\b\.?/iu', 'comprimidos', $nome);
        $nome = preg_replace('/(\d+)\s*(Caps|Cap|Cps|Cáps)\b\.?/iu', '$1 cápsulas', $nome);
        $nome = preg_replace('/\b(Caps|Cap|Cps|Cáps)\b\.?/iu', 'cápsulas', $nome);
        $nome = preg_replace('/(\d+)\s*(Unid|Und|Un)\b\.?/iu', '$1 unidades', $nome);
        $nome = preg_replace('/\b(Unid|Und|Un)\b\.?/iu', 'unidade', $nome);
        $nome = preg_replace('/(\d+)\s*Mg\/Ml\b/iu', '$1mg/mL', $nome);
        $nome = preg_replace('/(\d+)\s*Mg\b/iu', '$1mg', $nome);
        $nome = preg_replace('/(\d+)\s*Mcg\b/iu', '$1mcg', $nome);
        $nome = preg_replace('/(\d+)\s*Ml\b/iu', '$1mL', $nome);
        $nome = preg_replace('/(\d+)\s*G\b/iu', '$1g', $nome);
        $nome = preg_replace('/\b(Gts|Gt|Gd)\b\.?/iu', 'gotas', $nome);
        $nome = preg_replace('/\bFrs\b\.?/iu', 'frascos', $nome);
        $nome = preg_replace('/\bFr\b\.?/iu', 'frasco', $nome);
        $nome = preg_replace('/\bBisn\b\.?/iu', 'bisnaga', $nome);
        $nome = preg_replace('/\bEnv\b\.?/iu', 'envelope', $nome);
        $nome = preg_replace('/(\d+)\s*(Sache|Sachê|Sach|Saches)\b\.?/iu', '$1 sachês', $nome);
        $nome = preg_replace('/\b(Sache|Sachê|Sach)\b\.?/iu', 'sachê', $nome);
        $nome = preg_replace('/\bBl\b\.?/iu', 'blister', $nome);
        $nome = preg_replace('/\bDisp\b\.?/iu', 'display', $nome);
        $nome = preg_replace('/\bRev\b\.?/iu', 'revestidos', $nome);
        $nome = preg_replace('/\bEferv\b\.?/iu', 'efervescente', $nome);
        $nome = preg_replace('/\bMast\b\.?/iu', 'mastigável', $nome);
        $nome = preg_replace('/\bSol\b\.?/iu', 'solução', $nome);
        $nome = preg_replace('/\bSusp\b\.?/iu', 'suspensão', $nome);
        $nome = preg_replace('/\bXpe\b\.?/iu', 'xarope', $nome);
        $nome = preg_replace('/\bInj\b\.?/iu', 'injetável', $nome);
        $nome = preg_replace('/\b(Sb|Sab)\b\.?/iu', 'sabor', $nome);
        $nome = preg_replace('/\bGen\b\.?/iu', 'genérico', $nome);
        $nome = preg_replace('/\bMono\b\.?/iu', 'monoidratada', $nome);
        $nome = preg_replace('/\bMonoid\b\.?/iu', 'monoidratada', $nome);
        $nome = preg_replace('/(\d+)\s*X\s*(\d+)/iu', '$1 x $2', $nome);

        $palavrasMinusculas = [
            ' De ' => ' de ', ' Da ' => ' da ', ' Do ' => ' do ',
            ' Das ' => ' das ', ' Dos ' => ' dos ', ' E ' => ' e ',
            ' Para ' => ' para ', ' Com ' => ' com ', ' Sem ' => ' sem ',
            ' Sabor ' => ' sabor ', ' Por ' => ' por '
        ];
        $nome = str_replace(array_keys($palavrasMinusculas), array_values($palavrasMinusculas), $nome);

        $nome = preg_replace('/\s+/', ' ', trim($nome));

        return mb_strtoupper(mb_substr($nome, 0, 1, 'UTF-8'), 'UTF-8') .
               mb_substr($nome, 1, null, 'UTF-8');
    }
}
