<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Categorias de avaliador — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p>Categorias livres (ex.: professor, área, TI) usadas pelo modo de designação "sorteio por categoria" das etapas deste concurso.</p>

<p><a href="<?php echo url('categoriasAvaliador/novo/' . (int) $concurso['id']); ?>">+ Nova categoria</a></p>

<?php if (empty($categorias)): ?>
    <p>Nenhuma categoria cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Ações</th></tr>
        <?php foreach ($categorias as $categoria): ?>
        <tr>
            <td><?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="<?php echo url('categoriasAvaliador/editar/' . (int) $categoria['id']); ?>">Editar</a>
                <form method="post" action="<?php echo url('categoriasAvaliador/remover'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $categoria['id']; ?>">
                    <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
                    <button type="submit" onclick="return confirm('Remover esta categoria? Avaliadores vinculados a ela perdem a categoria.');">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
