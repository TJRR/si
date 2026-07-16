<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Feedback — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('participante/index'); ?>">Voltar à minha inscrição</a></p>

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
