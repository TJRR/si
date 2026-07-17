<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if (!empty($banners)): ?>
<section class="site-banners" aria-label="Avisos">
    <?php foreach ($banners as $banner): ?>
    <div class="site-banner"
         <?php if (!empty($banner['imagem_desktop_path'])): ?>
         style="background-image:url('<?php echo htmlspecialchars(config('base_path') . '/assets/' . $banner['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>')"
         <?php else: ?>
         style="background-color:<?php echo htmlspecialchars((string) $banner['cor_fundo'], ENT_QUOTES, 'UTF-8'); ?>"
         <?php endif; ?>>
        <?php if (!empty($banner['imagem_alt'])): ?>
            <span class="sr-only"><?php echo htmlspecialchars($banner['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <div class="site-banner-conteudo"><?php echo $banner['conteudo_html']; ?></div>
        <?php
        $destino = null;
        if (!empty($banner['cta_destino_valor'])) {
            $destino = $banner['cta_destino_tipo'] === 'ancora' ? '#' . $banner['cta_destino_valor'] : $banner['cta_destino_valor'];
        }
        ?>
        <?php if (!empty($banner['cta_titulo']) && $destino !== null): ?>
            <a href="<?php echo htmlspecialchars($destino, ENT_QUOTES, 'UTF-8'); ?>"
               class="site-banner-cta site-banner-cta-<?php echo htmlspecialchars($banner['cta_posicao'], ENT_QUOTES, 'UTF-8'); ?> efeito-<?php echo htmlspecialchars($banner['cta_efeito_hover'], ENT_QUOTES, 'UTF-8'); ?>"
               <?php echo $banner['cta_destino_tipo'] === 'externo' ? 'target="_blank" rel="noopener"' : ''; ?>>
                <?php echo htmlspecialchars($banner['cta_titulo'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
