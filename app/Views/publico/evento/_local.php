<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <div class="evento-local">
            <?php if (!empty($dadosSecao['endereco'])): ?>
                <p class="evento-local-endereco"><?php echo htmlspecialchars($dadosSecao['endereco'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if (!empty($dadosSecao['mapa_embed_url'])): ?>
                <div class="evento-local-mapa">
                    <iframe src="<?php echo htmlspecialchars($dadosSecao['mapa_embed_url'], ENT_QUOTES, 'UTF-8'); ?>" title="Mapa do local do evento" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            <?php elseif (!empty($dadosSecao['imagem_path'])): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $dadosSecao['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $dadosSecao['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="evento-local-imagem">
            <?php endif; ?>

            <?php if (!empty($dadosSecao['mapa_link'])): ?>
                <a href="<?php echo htmlspecialchars($dadosSecao['mapa_link'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-bordered" target="_blank" rel="noopener noreferrer">Abrir no mapa</a>
            <?php endif; ?>
        </div>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
