<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<p>
    <strong><?php echo htmlspecialchars($horario['tema'], ENT_QUOTES, 'UTF-8'); ?></strong>
    — <?php echo htmlspecialchars(formatarDataHora($horario['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
    às <?php echo htmlspecialchars(formatarDataHora($horario['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
    <?php echo sufixoFusoHorario(); ?>
</p>

<?php include __DIR__ . '/../_presenca_relatorio.php'; ?>
