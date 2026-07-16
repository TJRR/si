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
                <button type="submit" class="btn-icone" title="Reabrir etapa" onclick="return confirm('Reabrir apaga o resultado publicado e libera novas notas. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </button>
            </form>
        <?php else: ?>
            <strong>Prévia (ainda não publicada)</strong> — recalculada a cada acesso conforme as notas lançadas até agora.
            <form method="post" action="<?php echo url('resultados/publicarEtapa'); ?>" style="display:inline;">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit" class="btn-icone" title="Confirmar e publicar" onclick="return confirm('Publicar congela este ranking e bloqueia novas notas nesta etapa. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </button>
            </form>
        <?php endif; ?>
    </p>

    <table border="1" cellpadding="6">
        <tr><th>#</th><th>Submissão</th><th>Equipe</th><th>NE</th><th>Classificado</th><th>Ações</th></tr>
        <?php foreach ($ranking as $posicao => $linha): ?>
        <?php
        $urlSubmissao = url('resultados/popupSubmissao/' . (int) $linha['submissao_id']);
        $urlNotas = url('resultados/popupNotas/' . (int) $linha['submissao_id']);
        ?>
        <tr>
            <td><?php echo $posicao + 1; ?></td>
            <td>#<?php echo (int) $linha['submissao_id']; ?></td>
            <td><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $linha['ne'] !== null ? number_format((float) $linha['ne'], 2, ',', '.') : 'sem notas ainda'; ?></td>
            <td><?php echo !empty($linha['classificado']) ? 'Sim' : 'Não'; ?></td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo htmlspecialchars($urlSubmissao, ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Ver submissão"
                       onclick="abrirModalUrl('Conteúdo da submissão', this.href); return false;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </a>
                    <a href="<?php echo htmlspecialchars($urlNotas, ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Ver avaliações"
                       onclick="abrirModalUrl('Avaliações', this.href); return false;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
