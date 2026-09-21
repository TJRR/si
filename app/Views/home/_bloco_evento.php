<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php foreach ($blocosEvento as $bloco): ?>
    <section class="site-section" id="evento-divulgacao-<?php echo (int) $bloco['evento_id']; ?>">
        <div class="site-section-inner site-section-com-imagem site-section-com-imagem-<?php echo htmlspecialchars($bloco['imagem_posicao'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (!empty($bloco['imagem_path'])): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $bloco['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $bloco['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="section-imagem">
            <?php endif; ?>
            <div>
                <h2 class="section-title"><?php echo htmlspecialchars($bloco['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="section-text"><?php echo $bloco['conteudo_html']; ?></div>
                <a href="<?php echo url('eventoInscricao/index/' . (int) $bloco['evento_id']); ?>" class="btn btn-cta btn-cta-<?php echo htmlspecialchars($bloco['cta_alinhamento'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(!empty($bloco['cta_titulo']) ? $bloco['cta_titulo'] : 'Inscreva-se', ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </div>
    </section>
<?php endforeach; ?>
