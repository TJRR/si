<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Formulários Dinâmicos</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>
<p><a href="<?php echo url('formularios/novo'); ?>">+ Novo formulario</a></p>

<?php if (empty($formularios)): ?>
    <p>Nenhum formulário cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Versão</th><th>Status</th><th>Ações</th></tr>
        <?php foreach ($formularios as $formulario): ?>
        <tr>
            <td><?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $formulario['versao']; ?></td>
            <td><?php echo htmlspecialchars($formulario['status'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="<?php echo url('formularios/editar/' . (int) $formulario['id']); ?>">Editar</a>
                |
                <a href="<?php echo url('campos/index/' . (int) $formulario['id']); ?>">Campos</a>

                <?php if ($formulario['status'] === 'rascunho'): ?>
                    |
                    <form method="post" action="<?php echo url('formularios/publicar'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                        <button type="submit">Publicar</button>
                    </form>
                <?php endif; ?>

                <?php if ($formulario['status'] === 'publicado'): ?>
                    |
                    <form method="post" action="<?php echo url('formularios/arquivar'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                        <button type="submit">Arquivar</button>
                    </form>
                    |
                    <form method="post" action="<?php echo url('formularios/duplicar'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                        <button type="submit">Duplicar (nova versão)</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
