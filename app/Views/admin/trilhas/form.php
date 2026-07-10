<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $trilha === null ? 'Nova trilha' : 'Editar trilha'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $concurso['id']); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $trilha === null ? url('trilhas/novo/' . (int) $concurso['id']) : url('trilhas/editar/' . (int) $trilha['id']); ?>">
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($trilha !== null ? $trilha['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Descrição:<br>
        <textarea name="descricao" rows="4" cols="50"><?php echo htmlspecialchars($trilha !== null ? (string) $trilha['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Ordem:
        <input type="number" name="ordem" value="<?php echo $trilha !== null ? (int) $trilha['ordem'] : 0; ?>">
    </label><br>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($trilha === null || $trilha['ativo']) ? 'checked' : ''; ?>>
        Ativa
    </label><br>

    <button type="submit">Salvar</button>
</form>
