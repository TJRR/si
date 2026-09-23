<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Abertura comum das secoes da pagina do Evento: ancora, cores proprias da
 * secao (quando o Admin cadastrou) e o cabecalho de etiqueta/titulo/texto.
 * Espera $dadosSecao e $ancoraSecao.
 */
$estiloSecao = '';

if (!empty($dadosSecao['cor_fundo'])) {
    $estiloSecao .= 'background:' . htmlspecialchars($dadosSecao['cor_fundo'], ENT_QUOTES, 'UTF-8') . ';';
}

if (!empty($dadosSecao['cor_texto'])) {
    $estiloSecao .= 'color:' . htmlspecialchars($dadosSecao['cor_texto'], ENT_QUOTES, 'UTF-8') . ';';
}
?>
<section class="site-section evento-secao" id="<?php echo htmlspecialchars($ancoraSecao, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo $estiloSecao; ?>">
    <div class="site-section-inner">
        <?php if (!empty($dadosSecao['etiqueta'])): ?>
            <p class="evento-secao-etiqueta"><?php echo htmlspecialchars($dadosSecao['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php if (!empty($dadosSecao['titulo'])): ?>
            <h2 class="section-title"><?php echo htmlspecialchars($dadosSecao['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php endif; ?>
        <?php if (!empty($dadosSecao['descricao_html'])): ?>
            <div class="section-text"><?php echo $dadosSecao['descricao_html']; ?></div>
        <?php endif; ?>
