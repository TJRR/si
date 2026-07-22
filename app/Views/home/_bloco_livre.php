<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<section class="site-section<?php echo !empty($alternado) ? ' site-section-alt' : ''; ?>" id="<?php echo htmlspecialchars($blocoLivre['secao_ancora'], ENT_QUOTES, 'UTF-8'); ?>">
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
        </div>
    </div>
</section>
