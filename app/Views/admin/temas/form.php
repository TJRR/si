<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $tema === null ? 'Novo tema/desafio' : 'Editar tema/desafio'; ?> — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('temas/index/' . (int) $trilha['id']); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $tema === null ? url('temas/novo/' . (int) $trilha['id']) : url('temas/editar/' . (int) $tema['id']); ?>">
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($tema !== null ? $tema['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Descrição longa:<br>
        <textarea name="descricao_longa" rows="6" cols="60"><?php echo htmlspecialchars($tema !== null ? (string) $tema['descricao_longa'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($tema === null || $tema['ativo']) ? 'checked' : ''; ?>>
        Ativo
    </label><br>

    <button type="submit">Salvar</button>
</form>
