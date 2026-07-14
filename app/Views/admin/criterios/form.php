<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $criterio === null ? 'Novo critério' : 'Editar critério'; ?> — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $criterio === null ? url('criterios/novo/' . (int) $etapa['id']) : url('criterios/editar/' . (int) $criterio['id']); ?>">
    <label>Código (usado nas fórmulas, ex.: C1):
        <input type="text" name="codigo" required value="<?php echo htmlspecialchars($criterio !== null ? $criterio['codigo'] : $codigoSugerido, ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($criterio !== null ? $criterio['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Descrição:<br>
        <textarea name="descricao" rows="3" cols="50"><?php echo htmlspecialchars($criterio !== null ? (string) $criterio['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Peso:
        <input type="text" name="peso" required value="<?php echo $criterio !== null ? number_format((float) $criterio['peso'], 2, ',', '') : ''; ?>">
    </label><br>

    <label>Escala mínima:
        <input type="text" name="escala_min" value="<?php echo $criterio !== null ? number_format((float) $criterio['escala_min'], 1, ',', '') : '0'; ?>">
    </label><br>

    <label>Escala máxima:
        <input type="text" name="escala_max" value="<?php echo $criterio !== null ? number_format((float) $criterio['escala_max'], 1, ',', '') : '10'; ?>">
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('criterios/index/' . (int) $etapa['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
