<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $concurso === null ? 'Novo concurso' : 'Editar concurso'; ?></h1>

<p><a href="<?php echo url('concursos/index'); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $concurso === null ? url('concursos/novo') : url('concursos/editar/' . (int) $concurso['id']); ?>">
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($concurso !== null ? $concurso['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Descricao:<br>
        <textarea name="descricao" rows="4" cols="50"><?php echo htmlspecialchars($concurso !== null ? (string) $concurso['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Data de inicio:
        <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($concurso !== null ? (string) $concurso['data_inicio'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Data de fim:
        <input type="date" name="data_fim" value="<?php echo htmlspecialchars($concurso !== null ? (string) $concurso['data_fim'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Status:
        <select name="status">
            <?php $statusAtual = $concurso !== null ? $concurso['status'] : 'rascunho'; ?>
            <option value="rascunho" <?php echo $statusAtual === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
            <option value="ativo" <?php echo $statusAtual === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
            <option value="encerrado" <?php echo $statusAtual === 'encerrado' ? 'selected' : ''; ?>>Encerrado</option>
        </select>
    </label><br>

    <button type="submit">Salvar</button>
</form>
