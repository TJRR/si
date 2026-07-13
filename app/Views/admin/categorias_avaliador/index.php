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
                <div class="acoes-icones">
                    <a href="<?php echo url('categoriasAvaliador/editar/' . (int) $categoria['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form method="post" action="<?php echo url('categoriasAvaliador/remover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $categoria['id']; ?>">
                        <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
                        <button type="submit" class="btn-icone" title="Remover" onclick="return confirm('Remover esta categoria? Avaliadores vinculados a ela perdem a categoria.');">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
