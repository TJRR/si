<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51: cor de fundo/texto e segundo botao existem so' nos blocos de
// Evento (evento_blocos_conteudo) - no Concurso essas colunas nao existem,
// por isso tudo aqui e' lido com isset(), nunca direto.
$estiloBloco = '';

if (!empty($blocoLivre['cor_fundo'])) {
    $estiloBloco .= 'background:' . htmlspecialchars($blocoLivre['cor_fundo'], ENT_QUOTES, 'UTF-8') . ';';
}

if (!empty($blocoLivre['cor_texto'])) {
    $estiloBloco .= 'color:' . htmlspecialchars($blocoLivre['cor_texto'], ENT_QUOTES, 'UTF-8') . ';';
}
?>
<section class="site-section<?php echo !empty($alternado) ? ' site-section-alt' : ''; ?>" id="<?php echo htmlspecialchars($blocoLivre['secao_ancora'], ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo $estiloBloco; ?>">
    <div class="site-section-inner site-section-com-imagem site-section-com-imagem-<?php echo htmlspecialchars($blocoLivre['imagem_posicao'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($blocoLivre['imagem_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $blocoLivre['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $blocoLivre['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="section-imagem">
        <?php endif; ?>
        <div>
            <h2 class="section-title"><?php echo htmlspecialchars($blocoLivre['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="section-text"><?php echo $blocoLivre['conteudo_html']; ?></div>
            <?php if (!empty($blocoLivre['cta_titulo']) && !empty($blocoLivre['cta_link'])): ?>
                <a href="<?php echo htmlspecialchars($blocoLivre['cta_link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-cta btn-cta-<?php echo htmlspecialchars($blocoLivre['cta_alinhamento'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($blocoLivre['cta_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
            <?php if (!empty($blocoLivre['cta2_titulo']) && !empty($blocoLivre['cta2_link'])): ?>
                <a href="<?php echo htmlspecialchars($blocoLivre['cta2_link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-bordered btn-cta btn-cta-<?php echo htmlspecialchars($blocoLivre['cta_alinhamento'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($blocoLivre['cta2_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
