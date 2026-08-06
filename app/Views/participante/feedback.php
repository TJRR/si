<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Notas e Feedback — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('participante/index'); ?>">Voltar à minha inscrição</a></p>

<?php if (!empty($notasPorCriterioEAvaliador)): ?>
    <h2>Notas por critério</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Critério</th>
            <th>Peso</th>
            <?php for ($ordinal = 1; $ordinal <= $totalAvaliadores; $ordinal++): ?>
                <th>Avaliador <?php echo $ordinal; ?></th>
            <?php endfor; ?>
            <th>Média</th>
        </tr>
        <?php foreach ($criterios as $criterio): ?>
            <?php $criterioId = (int) $criterio['id']; ?>
            <?php if (empty($notasPorCriterioEAvaliador[$criterioId])): ?>
                <?php continue; ?>
            <?php endif; ?>
            <tr>
                <td><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo number_format((float) $criterio['peso'], 2, ',', ''); ?></td>
                <?php for ($ordinal = 1; $ordinal <= $totalAvaliadores; $ordinal++): ?>
                    <td>
                        <?php echo isset($notasPorCriterioEAvaliador[$criterioId][$ordinal])
                            ? htmlspecialchars((string) $notasPorCriterioEAvaliador[$criterioId][$ordinal], ENT_QUOTES, 'UTF-8')
                            : '—'; ?>
                    </td>
                <?php endfor; ?>
                <td>
                    <strong>
                        <?php echo isset($mediaPorCriterioId[$criterioId])
                            ? number_format($mediaPorCriterioId[$criterioId], 2, ',', '')
                            : '—'; ?>
                    </strong>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($notaFinal !== null): ?>
        <p style="margin-top:1em;"><strong>Nota final da equipe nesta etapa: <?php echo number_format($notaFinal, 2, ',', ''); ?></strong></p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($etapa['modo_feedback_avaliador'] === 'criterio'): ?>
    <?php if (empty($feedbacksPorCriterio)): ?>
        <p>Nenhum feedback registrado para esta submissão.</p>
    <?php else: ?>
        <?php foreach ($criterios as $criterio): ?>
            <?php if (empty($feedbacksPorCriterio[(int) $criterio['id']])): ?>
                <?php continue; ?>
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php foreach ($feedbacksPorCriterio[(int) $criterio['id']] as $texto): ?>
                <p><?php echo nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8')); ?></p>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
<?php else: ?>
    <?php if (empty($feedbacksPorSubmissao)): ?>
        <p>Nenhum feedback registrado para esta submissão.</p>
    <?php else: ?>
        <?php foreach ($feedbacksPorSubmissao as $texto): ?>
            <p><?php echo nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8')); ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
