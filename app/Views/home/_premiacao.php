<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if ($blocoPremiacao !== null || !empty($premios)): ?>
<section class="site-section site-section-alt" id="premiacao">
    <div class="site-section-inner">
        <h2 class="section-title"><?php echo $blocoPremiacao !== null ? htmlspecialchars($blocoPremiacao['titulo'], ENT_QUOTES, 'UTF-8') : 'Premiação'; ?></h2>

        <?php if (!empty($premios)): ?>
            <div class="site-premios-grid">
                <?php foreach ($premios as $premio): ?>
                    <div class="admin-card site-premio-card">
                        <?php if (!empty($premio['imagem_path'])): ?>
                            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $premio['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $premio['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="site-premio-imagem">
                        <?php endif; ?>
                        <strong class="site-premio-posicao"><?php echo (int) $premio['posicao']; ?>º lugar</strong>
                        <p><?php echo nl2br(htmlspecialchars($premio['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($blocoPremiacao !== null && !empty($blocoPremiacao['conteudo_html'])): ?>
            <div class="section-text"><?php echo $blocoPremiacao['conteudo_html']; ?></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
