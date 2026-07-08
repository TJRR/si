<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Regras de desempate — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar as trilhas</a></p>
<p><a href="<?php echo url('desempate/novo/' . (int) $trilha['id']); ?>">+ Novo criterio de desempate</a></p>

<p>Ordem de aplicacao em caso de empate na Nota Final (1a linha tem prioridade):</p>

<?php if (empty($regras)): ?>
    <p>Nenhuma regra de desempate cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Etapa</th><th>Criterio</th><th>Direcao</th><th>Acoes</th></tr>
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
