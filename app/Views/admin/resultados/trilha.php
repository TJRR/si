<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Resultado final — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<?php if ($erro !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php elseif (empty($ranking)): ?>
    <p>Nenhuma equipe com todas as etapas publicadas ainda — publique o resultado de cada etapa da trilha antes de calcular a nota final.</p>
<?php else: ?>
    <p>
        <?php if ($publicado): ?>
            <strong>Resultado final publicado.</strong>
            <form method="post" action="<?php echo url('resultados/reabrirTrilha'); ?>" style="display:inline;">
                <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                <button type="submit" class="btn-secundario" onclick="return confirm('Reabrir apaga o resultado final publicado. Confirmar?');">Reabrir</button>
            </form>
        <?php else: ?>
            <strong>Prévia (ainda não publicada)</strong> — recalculada a cada acesso.
            <form method="post" action="<?php echo url('resultados/publicarTrilha'); ?>" style="display:inline;">
                <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                <button type="submit" onclick="return confirm('Publicar congela a colocação final desta trilha. Confirmar?');">Confirmar e publicar</button>
            </form>
        <?php endif; ?>
    </p>

    <table border="1" cellpadding="6">
        <tr><th>Colocação</th><th>Equipe</th><th>NF</th></tr>
        <?php foreach ($ranking as $linha): ?>
        <tr>
            <td><?php echo (int) $linha['colocacao']; ?></td>
            <td><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $linha['nf'], 2, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
