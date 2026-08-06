<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Lançar notas —
    <?php if ($sigiloCego && $submissao['numero_sigilo_etapa'] !== null): ?>
        Equipe <?php echo (int) $submissao['numero_sigilo_etapa']; ?>
    <?php else: ?>
        Submissão #<?php echo (int) $submissao['id']; ?>
    <?php endif; ?>
</h1>

<p><a href="<?php echo url('avaliacao/submissoes/' . (int) $etapa['id']); ?>">Voltar às submissões</a></p>

<?php if ($sigiloCego): ?>
    <p><em>Avaliação cega: dados de equipe/participantes ocultos.</em></p>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($resultadoPublicado): ?>
    <p><strong>O resultado desta etapa já foi publicado — as notas abaixo são apenas para consulta.</strong></p>
<?php elseif ($avaliacaoTravada): ?>
    <p><strong>Sua avaliação desta submissão já foi concluída — as notas abaixo são apenas para consulta.</strong></p>
<?php else: ?>
    <p id="progresso-avaliacao"><?php echo (int) $criteriosJaNotados; ?> de <?php echo (int) $totalCriterios; ?> critérios avaliados</p>
<?php endif; ?>
