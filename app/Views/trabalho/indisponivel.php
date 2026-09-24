<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/../eventoApp/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/../eventoApp/_ajuda_card.php'; ?>
        <div class="admin-card">
            <h2>Submissão de trabalhos</h2>
            <?php if (!empty($motivo)): ?>
                <p><?php echo htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <p>A submissão de trabalhos do evento "<?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>" não está disponível no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
