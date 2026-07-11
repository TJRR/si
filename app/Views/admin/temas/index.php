<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Temas/Desafios de <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>
<p><a href="<?php echo url('temas/novo/' . (int) $trilha['id']); ?>">+ Novo tema/desafio</a></p>

<?php if (empty($temas)): ?>
    <p>Nenhum tema/desafio cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Ativo</th><th>Ações</th></tr>
        <?php foreach ($temas as $tema): ?>
        <tr>
            <td><?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $tema['ativo'] ? 'Sim' : 'Não'; ?></td>
            <td>
                <a href="<?php echo url('temas/editar/' . (int) $tema['id']); ?>">Editar</a>
                <form method="post" action="<?php echo url('temas/remover'); ?>" style="display:inline;" onsubmit="return confirm('Remover este tema/desafio? Só funciona se ele ainda não tiver equipes vinculadas.');">
                    <input type="hidden" name="id" value="<?php echo (int) $tema['id']; ?>">
                    <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                    <button type="submit" class="btn-secundario">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
