<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if (!empty($slides)): ?>
<section class="site-slideshow" id="slideshow-principal" aria-roledescription="carrossel" aria-label="Destaques">
    <div class="site-slideshow-trilho">
        <?php foreach ($slides as $indice => $slide): ?>
        <div class="site-slide<?php echo $indice === 0 ? ' ativo' : ''; ?>" data-slide-indice="<?php echo $indice; ?>"
             style="background-image:url('<?php echo htmlspecialchars(config('base_path') . '/assets/' . $slide['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>')">
            <img class="site-slide-imagem-mobile" src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . ($slide['imagem_mobile_path'] ?: $slide['imagem_desktop_path']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($slide['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="site-slide-conteudo">
                <div class="site-slide-titulo"><?php echo $slide['titulo_html']; ?></div>
                <?php if (!empty($slide['separador_cor'])): ?>
                    <div class="site-slide-separador" style="background-color:<?php echo htmlspecialchars($slide['separador_cor'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <?php endif; ?>
                <?php if (!empty($slide['cta_titulo']) && !empty($slide['cta_link'])): ?>
                    <a href="<?php echo htmlspecialchars($slide['cta_link'], ENT_QUOTES, 'UTF-8'); ?>"
                       target="<?php echo htmlspecialchars($slide['cta_target'], ENT_QUOTES, 'UTF-8'); ?>"
                       <?php echo $slide['cta_target'] === '_blank' ? 'rel="noopener"' : ''; ?>
                       class="site-slide-cta site-slide-cta-<?php echo htmlspecialchars($slide['cta_tamanho'], ENT_QUOTES, 'UTF-8'); ?> efeito-<?php echo htmlspecialchars($slide['cta_efeito_hover'], ENT_QUOTES, 'UTF-8'); ?>"
                       style="<?php echo $slide['cta_cor_fundo'] ? 'background-color:' . htmlspecialchars($slide['cta_cor_fundo'], ENT_QUOTES, 'UTF-8') . ';' : ''; ?><?php echo $slide['cta_cor_texto'] ? 'color:' . htmlspecialchars($slide['cta_cor_texto'], ENT_QUOTES, 'UTF-8') . ';' : ''; ?>">
                        <?php echo htmlspecialchars($slide['cta_titulo'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($slides) > 1): ?>
    <button type="button" class="site-slideshow-seta site-slideshow-seta-anterior" data-slideshow-anterior aria-label="Slide anterior">‹</button>
    <button type="button" class="site-slideshow-seta site-slideshow-seta-proxima" data-slideshow-proxima aria-label="Próximo slide">›</button>
    <div class="site-slideshow-marcadores" role="tablist" aria-label="Selecionar slide">
        <?php foreach ($slides as $indice => $slide): ?>
            <button type="button" class="site-slideshow-marcador<?php echo $indice === 0 ? ' ativo' : ''; ?>" data-slideshow-ir="<?php echo $indice; ?>" role="tab" aria-label="Ir para o slide <?php echo $indice + 1; ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
