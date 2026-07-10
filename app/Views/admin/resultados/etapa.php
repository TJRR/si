<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Resultado — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $etapa['trilha_id']); ?>">Voltar às etapas</a></p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<?php if ($erro !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php elseif (empty($ranking)): ?>
    <p>Nenhuma submissão encontrada nesta etapa ainda.</p>
<?php else: ?>
    <p>
        <?php if ($publicado): ?>
            <strong>Resultado publicado.</strong> Novas notas ficam bloqueadas para as submissões desta etapa.
            <form method="post" action="<?php echo url('resultados/reabrirEtapa'); ?>" style="display:inline;">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit" class="btn-secundario" onclick="return confirm('Reabrir apaga o resultado publicado e libera novas notas. Confirmar?');">Reabrir etapa</button>
            </form>
        <?php else: ?>
            <strong>Prévia (ainda não publicada)</strong> — recalculada a cada acesso conforme as notas lançadas até agora.
            <form method="post" action="<?php echo url('resultados/publicarEtapa'); ?>" style="display:inline;">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit" onclick="return confirm('Publicar congela este ranking e bloqueia novas notas nesta etapa. Confirmar?');">Confirmar e publicar</button>
            </form>
        <?php endif; ?>
    </p>

    <table border="1" cellpadding="6">
        <tr><th>#</th><th>Submissão</th><th>Equipe</th><th>NE</th><th>Classificado</th></tr>
        <?php foreach ($ranking as $posicao => $linha): ?>
        <tr>
            <td><?php echo $posicao + 1; ?></td>
            <td>#<?php echo (int) $linha['submissao_id']; ?></td>
            <td><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $linha['ne'] !== null ? number_format((float) $linha['ne'], 2, ',', '.') : 'sem notas ainda'; ?></td>
            <td><?php echo !empty($linha['classificado']) ? 'Sim' : 'Não'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
