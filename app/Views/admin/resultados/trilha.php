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
                <button type="submit" class="btn-icone" title="Reabrir" onclick="return confirm('Reabrir apaga o resultado final publicado. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </button>
            </form>
        <?php else: ?>
            <strong>Prévia (ainda não publicada)</strong> — recalculada a cada acesso.
            <form method="post" action="<?php echo url('resultados/publicarTrilha'); ?>" style="display:inline;">
                <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                <button type="submit" class="btn-icone" title="Confirmar e publicar" onclick="return confirm('Publicar congela a colocação final desta trilha. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </button>
            </form>
        <?php endif; ?>
    </p>

    <table border="1" cellpadding="6">
        <tr><th>Colocação</th><th>Equipe</th><th>NF</th><?php echo $publicado ? '<th>Destaque público</th>' : ''; ?></tr>
        <?php foreach ($ranking as $linha): ?>
        <tr>
            <td><?php echo (int) $linha['colocacao']; ?></td>
            <td><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $linha['nf'], 2, ',', '.'); ?></td>
            <?php if ($publicado): ?>
            <td>
                <a href="<?php echo url('resultados/editarDestaque/' . (int) $linha['id']); ?>" class="btn-icone" title="Editar resumo/imagem de destaque">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
