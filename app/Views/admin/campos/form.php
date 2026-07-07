<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $campo === null ? 'Novo campo' : 'Editar campo'; ?> — <?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('campos/index/' . (int) $formulario['id']); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php
$configAtual = [];
if ($campo !== null && $campo['config_json'] !== null) {
    $configAtual = json_decode($campo['config_json'], true);
}
$tipoAtual = $campo !== null ? $campo['tipo'] : 'texto';
?>

<form method="post" action="<?php echo $campo === null ? url('campos/novo/' . (int) $formulario['id']) : url('campos/editar/' . (int) $campo['id']); ?>">
    <label>Rotulo:
        <input type="text" name="rotulo" required value="<?php echo htmlspecialchars($campo !== null ? $campo['rotulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Tipo:
        <select name="tipo" id="campo-tipo">
            <?php foreach ($tipos as $chave => $rotulo): ?>
                <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $tipoAtual === $chave ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>
        <input type="checkbox" name="obrigatorio" value="1" <?php echo ($campo !== null && $campo['obrigatorio']) ? 'checked' : ''; ?>>
        Obrigatorio
    </label><br>

    <div id="config-grupo-participantes" style="display:none;">
        <label>Minimo de participantes:
            <input type="number" name="minimo_repeticoes" min="1" value="<?php echo isset($configAtual['minimo_repeticoes']) ? (int) $configAtual['minimo_repeticoes'] : 1; ?>">
        </label><br>
        <label>Maximo de participantes:
            <input type="number" name="maximo_repeticoes" min="1" value="<?php echo isset($configAtual['maximo_repeticoes']) ? (int) $configAtual['maximo_repeticoes'] : 10; ?>">
        </label><br>
    </div>

    <button type="submit">Salvar</button>
</form>

<script src="<?php echo config('base_path'); ?>/assets/js/construtor-campo.js"></script>
