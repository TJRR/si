<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Abertura comum das secoes da pagina do Evento: ancora, cores proprias da
 * secao e o cabecalho de etiqueta, titulo e texto. Espera $dadosSecao,
 * $ancoraSecao e $secao; $cabecalhoProprio = true pula o cabecalho padrao
 * (secao que desenha o proprio, como a de submissao em duas colunas).
 *
 * Reabertura da Fase 51: o fundo vai de borda a borda da janela e o
 * conteudo fica no conteiner largo da pagina (.evento-conteudo). A etiqueta
 * usa a cor de destaque do tema sobre fundo claro e a propria cor do texto
 * sobre fundo forte, como na identidade visual aprovada.
 */
$corFundoSecao = isset($dadosSecao['cor_fundo']) ? $dadosSecao['cor_fundo'] : null;
$corTextoSecao = isset($dadosSecao['cor_texto']) ? $dadosSecao['cor_texto'] : null;
$classeFundo = corEhClara($corFundoSecao) ? 'evento-fundo-claro' : 'evento-fundo-forte';
?>
<section class="evento-secao evento-secao-<?php echo htmlspecialchars($secao['tipo'], ENT_QUOTES, 'UTF-8'); ?> <?php echo $classeFundo; ?>" id="<?php echo htmlspecialchars($ancoraSecao, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo estiloDeCores($corFundoSecao, $corTextoSecao); ?>">
    <div class="evento-conteudo">
        <?php if (empty($cabecalhoProprio)): ?>
            <?php include __DIR__ . '/_secao_cabecalho.php'; ?>
        <?php endif; ?>
