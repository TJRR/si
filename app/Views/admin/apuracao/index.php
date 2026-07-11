<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Apuração — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<h2>Fórmula da nota final</h2>
<p>Define como a Nota Final (NF) da trilha é calculada a partir das notas das etapas. Ex.: Editais 12/2026 e 13/2026
    usam NF = NE2 x 0,4 + NE3 x 0,6.</p>

<?php if (empty($etapasDaTrilha)): ?>
    <p>Nenhuma etapa cadastrada nesta trilha ainda.</p>
<?php else: ?>
    <p>Variáveis disponíveis (nota de cada etapa, pela ordem):</p>
    <ul>
        <?php foreach ($etapasDaTrilha as $etapaDaTrilha): ?>
            <li><code>NE<?php echo (int) $etapaDaTrilha['ordem']; ?></code>
                — <?php echo htmlspecialchars($etapaDaTrilha['nome'], ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
    </ul>

    <form method="post" action="<?php echo url('formulas/trilha/' . (int) $trilha['id']); ?>">
        <label>Expressão (ex.: NE2*0.4 + NE3*0.6):<br>
            <textarea name="expressao" rows="3" cols="70" required><?php echo htmlspecialchars((string) $expressaoAtual, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label><br>
        <button type="submit" name="acao" value="salvar">Salvar fórmula</button>
    </form>
<?php endif; ?>

<h2>Desempate</h2>
<p><a href="<?php echo url('desempate/novo/' . (int) $trilha['id']); ?>">+ Novo critério de desempate</a></p>
<p>Ordem de aplicação em caso de empate na Nota Final (1ª linha tem prioridade):</p>

<?php if (empty($regras)): ?>
    <p>Nenhuma regra de desempate cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Etapa</th><th>Critério</th><th>Direção</th><th>Ações</th></tr>
        <?php foreach ($regras as $regra): ?>
        <tr>
            <td><?php echo (int) $regra['ordem']; ?></td>
            <td><?php echo htmlspecialchars($regra['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($regra['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $regra['direcao'] === 'asc' ? 'Crescente' : 'Decrescente (maior nota vence)'; ?></td>
            <td>
                <form method="post" action="<?php echo url('desempate/mover'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                    <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                    <input type="hidden" name="direcao" value="cima">
                    <button type="submit">Cima</button>
                </form>
                <form method="post" action="<?php echo url('desempate/mover'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                    <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                    <input type="hidden" name="direcao" value="baixo">
                    <button type="submit">Baixo</button>
                </form>
                <form method="post" action="<?php echo url('desempate/remover'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                    <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                    <button type="submit">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Resultado final</h2>

<?php if ($erroResultado !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erroResultado, ENT_QUOTES, 'UTF-8'); ?></p>
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
