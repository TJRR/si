<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Lançar notas — Submissão #<?php echo (int) $submissao['id']; ?></h1>

<p><a href="<?php echo url('avaliacao/submissoes/' . (int) $etapa['id']); ?>">Voltar às submissões</a></p>

<?php if ($sigiloCego): ?>
    <p><em>Avaliação cega: dados de equipe/participantes ocultos.</em></p>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($resultadoPublicado): ?>
    <p><strong>O resultado desta etapa já foi publicado — as notas abaixo são apenas para consulta.</strong></p>
<?php endif; ?>

<form method="post" action="<?php echo url('avaliacao/notar/' . (int) $submissao['id']); ?>">
    <table border="1" cellpadding="6">
        <tr><th>Critério</th><th>Escala</th><th>Nota</th></tr>
        <?php foreach ($criterios as $criterio): ?>
        <tr>
            <td><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $criterio['escala_min'], 1, ',', '.'); ?> a <?php echo number_format((float) $criterio['escala_max'], 1, ',', '.'); ?></td>
            <td>
                <input type="text"
                       name="nota[<?php echo (int) $criterio['id']; ?>]"
                       value="<?php echo isset($notasAtuais[$criterio['id']]) ? htmlspecialchars((string) $notasAtuais[$criterio['id']]['nota'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                       <?php echo $resultadoPublicado ? 'readonly' : ''; ?>>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if (!$resultadoPublicado): ?>
        <button type="submit">Salvar notas</button>
    <?php endif; ?>
</form>
