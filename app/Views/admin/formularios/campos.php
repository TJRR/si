<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Campos de <?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('formularios/index'); ?>">Voltar aos formulários</a></p>

<p>Status do formulário: <strong><?php echo htmlspecialchars($formulario['status'], ENT_QUOTES, 'UTF-8'); ?></strong></p>

<?php $editavel = $formulario['status'] === 'rascunho'; ?>

<?php if (!$editavel): ?>
    <p style="color:#b06000;">Este formulário já foi publicado. Para alterar os campos, duplique-o (tela de Formulários) e edite a nova versão.</p>
<?php else: ?>
    <p><a href="<?php echo url('campos/novo/' . (int) $formulario['id']); ?>">+ Novo campo</a></p>
<?php endif; ?>

<?php if (empty($campos)): ?>
    <p>Nenhum campo cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Rótulo</th><th>Tipo</th><th>Obrigatório</th><th>Ações</th></tr>
        <?php foreach ($campos as $campo): ?>
        <tr>
            <td><?php echo (int) $campo['ordem']; ?></td>
            <td><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($campo['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $campo['obrigatorio'] ? 'Sim' : 'Não'; ?></td>
            <td>
                <?php if ($editavel): ?>
                    <a href="<?php echo url('campos/editar/' . (int) $campo['id']); ?>">Editar</a>

                    <form method="post" action="<?php echo url('campos/mover'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                        <input type="hidden" name="formulario_id" value="<?php echo (int) $formulario['id']; ?>">
                        <input type="hidden" name="direcao" value="cima">
                        <button type="submit">Cima</button>
                    </form>
                    <form method="post" action="<?php echo url('campos/mover'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                        <input type="hidden" name="formulario_id" value="<?php echo (int) $formulario['id']; ?>">
                        <input type="hidden" name="direcao" value="baixo">
                        <button type="submit">Baixo</button>
                    </form>
                    <form method="post" action="<?php echo url('campos/remover'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                        <input type="hidden" name="formulario_id" value="<?php echo (int) $formulario['id']; ?>">
                        <button type="submit">Remover</button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
