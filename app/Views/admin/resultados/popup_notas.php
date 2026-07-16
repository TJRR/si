<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if (empty($notasPorAvaliador)): ?>
    <p><em>Nenhuma nota lançada para esta submissão ainda.</em></p>
<?php else: ?>
    <?php foreach ($notasPorAvaliador as $grupo): ?>
        <h3><?php echo htmlspecialchars($grupo['avaliador_nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <table border="1" cellpadding="6" style="margin-bottom:1.5em;">
            <tr><th>Critério</th><th>Nota</th><th>Feedback</th></tr>
            <?php foreach ($grupo['notas'] as $nota): ?>
                <tr>
                    <td><?php echo htmlspecialchars($nota['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format((float) $nota['nota'], 1, ',', '.'); ?></td>
                    <td><?php echo !empty($nota['feedback']) ? nl2br(htmlspecialchars($nota['feedback'], ENT_QUOTES, 'UTF-8')) : '—'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
<?php endif; ?>
