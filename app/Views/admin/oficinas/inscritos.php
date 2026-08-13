<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<p><strong><?php echo htmlspecialchars($horario['tema'], ENT_QUOTES, 'UTF-8'); ?></strong> — <?php echo htmlspecialchars(formatarDataHora($horario['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> <?php echo sufixoFusoHorario(); ?></p>

<?php if (empty($inscritos)): ?>
    <p>Nenhuma equipe inscrita ainda.</p>
<?php else: ?>
    <table>
        <tr><th>Equipe</th><th>Inscrita em <?php echo sufixoFusoHorario(); ?></th></tr>
        <?php foreach ($inscritos as $inscrito): ?>
        <tr>
            <td><?php echo htmlspecialchars($inscrito['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($inscrito['inscrito_em']), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
