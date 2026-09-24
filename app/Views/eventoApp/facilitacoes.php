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
        <h2>Minhas facilitações</h2>
        <p>Atividades em que você está designado como facilitador. Informe o código de presença online abaixo para quem está participando de forma online confirmar presença.</p>

        <?php foreach ($facilitacoes as $facilitacao): ?>
        <div class="admin-card">
            <p><strong><?php echo htmlspecialchars($facilitacao['atividade_nome'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p>
                <?php echo htmlspecialchars(formatarDataHora($facilitacao['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars(formatarDataHora($facilitacao['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($facilitacao['local'])): ?>
                    , em <?php echo htmlspecialchars($facilitacao['local'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </p>

            <?php if ($facilitacao['modalidade'] === 'presencial'): ?>
                <p>Atividade presencial: os participantes confirmam presença lendo o código afixado na sala.</p>
            <?php elseif (!empty($facilitacao['codigo_presenca_online'])): ?>
                <p>Código de presença online: <strong style="font-family:'Courier New',Courier,monospace;font-size:1.4em;letter-spacing:2px;"><?php echo htmlspecialchars($facilitacao['codigo_presenca_online'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <p style="color:#555;font-size:0.9em;">Informe este código verbalmente para quem está participando online.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
