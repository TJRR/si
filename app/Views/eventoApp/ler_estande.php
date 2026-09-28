<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/estandes/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <?php
        $leitorEndpoint = url('eventoApp/validarEstande/' . (int) $evento['id']);
        $leitorTitulo = 'Registrar visita a estande';
        $leitorInstrucao = 'Aponte a câmera para o código do cartaz do estande ou digite-o abaixo.';
        $leitorRotuloCampo = 'Código de 6 caracteres do cartaz';
        require __DIR__ . '/_leitor_codigo.php';
        ?>
        <p><a href="<?php echo url('eventoApp/estandes/' . (int) $evento['id']); ?>">Ver meus pontos e os estandes</a></p>
    </div>
</div>
