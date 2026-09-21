<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $tituloTopo = 'Meus eventos';
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <p>Você está inscrito em mais de um evento. Escolha qual deseja abrir:</p>

        <ul>
            <?php foreach ($inscricoes as $inscricao): ?>
                <li>
                    <a href="<?php echo url('eventoApp/index/' . (int) $inscricao['evento_id']); ?>">
                        <?php echo htmlspecialchars($inscricao['evento_nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
