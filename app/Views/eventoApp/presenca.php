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
        <?php
        $leitorEndpoint = url('eventoApp/validarPresenca/' . (int) $evento['id']);
        $leitorTitulo = 'Ler código';
        $leitorInstrucao = 'Aponte a câmera para o código afixado no espaço da atividade ou no credenciamento, ou para o código que o responsável pela competição mostra, ou digite-o abaixo.';
        require __DIR__ . '/_leitor_codigo.php';
        ?>
    </div>
</div>
