<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $formulario === null ? 'Novo formulário' : 'Editar formulário'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('formularios/index/' . (int) $concurso['id']); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $formulario === null ? url('formularios/novo/' . (int) $concurso['id']) : url('formularios/editar/' . (int) $formulario['id']); ?>">
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($formulario !== null ? $formulario['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Descrição:<br>
        <textarea name="descricao" rows="4" cols="50"><?php echo htmlspecialchars($formulario !== null ? (string) $formulario['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <button type="submit">Salvar</button>
</form>

<?php if ($formulario !== null): ?>
    <p><a href="<?php echo url('campos/index/' . (int) $formulario['id']); ?>">Gerenciar campos deste formulário</a></p>
<?php endif; ?>
