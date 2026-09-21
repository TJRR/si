<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <?php
        $leitorEndpoint = url('eventoApp/validarPresenca/' . (int) $evento['id']);
        $leitorTitulo = 'Confirmar presença';
        $leitorInstrucao = 'Aponte a câmera para o código da atividade ou digite-o abaixo.';
        require __DIR__ . '/_leitor_codigo.php';
        ?>
    </div>
</div>
