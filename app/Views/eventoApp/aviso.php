<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <div class="admin-card">
            <h1><?php echo htmlspecialchars($comunicacao['assunto'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php echo $comunicacao['corpo_html']; ?>
        </div>
    </div>
</div>
