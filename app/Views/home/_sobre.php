<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if ($blocoSobre !== null): ?>
<section class="site-section site-section-com-imagem" id="sobre">
    <?php if (!empty($blocoSobre['imagem_path'])): ?>
        <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $blocoSobre['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $blocoSobre['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="section-imagem">
    <?php endif; ?>
    <div>
        <h2 class="section-title"><?php echo htmlspecialchars($blocoSobre['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="section-text"><?php echo $blocoSobre['conteudo_html']; ?></div>

        <?php
        $fasesDoProcesso = array_values(array_filter($cronograma, function ($item) {
            return $item['tipo'] === 'etapa';
        }));
        ?>
        <?php if (!empty($fasesDoProcesso)): ?>
        <ol class="site-fases-lista">
            <?php foreach ($fasesDoProcesso as $fase): ?>
                <li><strong><?php echo htmlspecialchars($fase['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></strong> — <?php echo htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>

        <?php if (!empty($blocoSobre['cta_titulo']) && !empty($blocoSobre['cta_link'])): ?>
            <a href="<?php echo htmlspecialchars($blocoSobre['cta_link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-cta"><?php echo htmlspecialchars($blocoSobre['cta_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endif; ?>

        <p><a href="<?php echo url('edicoes/index'); ?>">Ver edições anteriores →</a></p>
    </div>
</section>
<?php endif; ?>
