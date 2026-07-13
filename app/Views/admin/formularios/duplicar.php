<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Duplicar formulário — <?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('formularios/index/' . (int) $formulario['concurso_id']); ?>">Voltar aos formulários</a></p>

<p>Cria uma nova versão deste formulário (com os mesmos campos) no concurso escolhido abaixo.</p>

<form method="post" action="<?php echo url('formularios/duplicar'); ?>">
    <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
    <input type="hidden" name="concurso_origem_id" value="<?php echo (int) $formulario['concurso_id']; ?>">

    <label>Concurso de destino:
        <select name="concurso_id">
            <?php foreach ($concursos as $concurso): ?>
                <option value="<?php echo (int) $concurso['id']; ?>" <?php echo (int) $concurso['id'] === (int) $formulario['concurso_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <button type="submit">Confirmar duplicação</button>
</form>
