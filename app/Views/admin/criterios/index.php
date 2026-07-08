<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Criterios de <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $etapa['trilha_id']); ?>">Voltar as etapas</a></p>
<p><a href="<?php echo url('criterios/novo/' . (int) $etapa['id']); ?>">+ Novo criterio</a></p>

<p>Soma dos pesos: <strong><?php echo number_format($somaPesos, 2, ',', '.'); ?></strong>
    (a formula <em>media ponderada de criterios</em> usa esta soma como denominador — os editais 2026 usam pesos que somam 10)</p>

<?php if (empty($criterios)): ?>
    <p>Nenhum criterio cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Codigo</th><th>Nome</th><th>Peso</th><th>Escala</th><th>Acoes</th></tr>
        <?php foreach ($criterios as $criterio): ?>
        <tr>
            <td><?php echo (int) $criterio['ordem']; ?></td>
            <td><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $criterio['peso'], 2, ',', '.'); ?></td>
            <td><?php echo number_format((float) $criterio['escala_min'], 1, ',', '.'); ?> a <?php echo number_format((float) $criterio['escala_max'], 1, ',', '.'); ?></td>
            <td>
                <a href="<?php echo url('criterios/editar/' . (int) $criterio['id']); ?>">Editar</a>

                <form method="post" action="<?php echo url('criterios/mover'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $criterio['id']; ?>">
                    <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                    <input type="hidden" name="direcao" value="cima">
                    <button type="submit">Cima</button>
                </form>
                <form method="post" action="<?php echo url('criterios/mover'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $criterio['id']; ?>">
                    <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                    <input type="hidden" name="direcao" value="baixo">
                    <button type="submit">Baixo</button>
                </form>
                <form method="post" action="<?php echo url('criterios/remover'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $criterio['id']; ?>">
                    <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                    <button type="submit">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
