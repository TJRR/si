<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Etiqueta, titulo e texto de apoio de uma secao da pagina do Evento.
 * Usado por _secao_abre.php e, dentro da propria coluna, pelas secoes que
 * desenham o cabecalho em outro lugar (submissao, local).
 */
?>
<?php if (!empty($dadosSecao['etiqueta']) || !empty($dadosSecao['titulo']) || !empty($dadosSecao['descricao_html'])): ?>
    <div class="evento-secao-cabecalho">
        <?php if (!empty($dadosSecao['etiqueta'])): ?>
            <p class="evento-secao-etiqueta"><?php echo htmlspecialchars($dadosSecao['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php if (!empty($dadosSecao['titulo'])): ?>
            <h2 class="evento-secao-titulo"><?php echo htmlspecialchars($dadosSecao['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php endif; ?>
        <?php if (!empty($dadosSecao['descricao_html'])): ?>
            <div class="evento-secao-texto"><?php echo $dadosSecao['descricao_html']; ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
