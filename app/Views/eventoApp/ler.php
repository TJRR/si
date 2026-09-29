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
        <h2>Conectar com participante</h2>

        <?php if ((int) $config['ativo'] !== 1): ?>
            <?php /* Fase 55: com o modulo desligado no evento, nada de
            leitor na tela - ninguem le um cracha achando que vai pontuar. */ ?>
            <div class="admin-card">
                <p>As conexões não estão ativadas neste evento.</p>
            </div>
        <?php elseif (empty($dentroDaJanela)): ?>
            <div class="admin-card">
                <p>
                    As conexões só valem durante o evento, de
                    <?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a
                    <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>.
                </p>
            </div>
        <?php else: ?>
            <?php
            $leitorEndpoint = url('eventoApp/validarCodigo/' . (int) $evento['id']);
            $leitorTitulo = 'Ler o crachá de outra pessoa';
            $leitorInstrucao = 'Aponte a câmera para o código do crachá da outra pessoa ou digite-o abaixo. Uma leitura só já conecta e pontua as duas.';
            $leitorRotuloCampo = 'Código de 6 caracteres do crachá';
            require __DIR__ . '/_leitor_codigo.php';
            ?>
        <?php endif; ?>

        <div class="admin-card">
            <p>
                <strong>Seus pontos em conexões: <?php echo (int) $resumo['total_pontos']; ?></strong>
                <br>
                <?php echo (int) $resumo['total_conexoes']; ?>
                <?php echo (int) $resumo['total_conexoes'] === 1 ? 'pessoa conectada' : 'pessoas conectadas'; ?>
            </p>
        </div>

        <p><a href="<?php echo url('eventoApp/conexoes/' . (int) $evento['id']); ?>">Ver minhas conexões</a></p>
    </div>
</div>
