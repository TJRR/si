<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $ehEdicao = $campo !== null && isset($campo['id']); ?>
<h1><?php echo $ehEdicao ? 'Editar campo' : 'Novo campo'; ?>: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php
$tipoAtual = $campo !== null ? $campo['tipo'] : 'texto';
$opcoesAtuais = '';

if ($tipoAtual === 'lista_opcoes' && $campo !== null) {
    $configAtual = isset($campo['config']) ? $campo['config'] : (isset($campo['config_json']) && $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : null);
    $opcoesAtuais = $configAtual !== null && isset($configAtual['opcoes']) ? implode("\n", $configAtual['opcoes']) : '';
}
?>

<form method="post" action="<?php echo $ehEdicao ? url('eventoFormulario/editar/' . (int) $campo['id']) : url('eventoFormulario/novo/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Rótulo:
        <input type="text" name="rotulo" required value="<?php echo htmlspecialchars($campo !== null ? (string) $campo['rotulo'] : '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" size="50">
    </label><br>

    <label>Tipo:
        <select name="tipo" id="campo-tipo">
            <?php foreach (\App\Controllers\EventoFormularioAdminController::TIPOS as $valor => $rotulo): ?>
                <option value="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $tipoAtual === $valor ? 'selected' : ''; ?>><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <div id="config-lista-opcoes" style="display: <?php echo $tipoAtual === 'lista_opcoes' ? 'block' : 'none'; ?>;">
        <label>Opções da lista (uma por linha):<br>
            <textarea name="opcoes" rows="5" cols="40"><?php echo htmlspecialchars($opcoesAtuais, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
    </div>

    <label>
        <input type="checkbox" name="obrigatorio" value="1" <?php echo ($campo !== null && $campo['obrigatorio']) ? 'checked' : ''; ?>>
        Obrigatório
    </label><br>

    <label>Texto de ajuda (aparece abaixo do campo na inscrição):<br>
        <input type="text" name="texto_ajuda" value="<?php echo htmlspecialchars($campo !== null ? (string) $campo['texto_ajuda'] : '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" size="60">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('eventoFormulario/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>

<script>
(function () {
    var selectTipo = document.getElementById('campo-tipo');
    var blocoOpcoes = document.getElementById('config-lista-opcoes');

    selectTipo.addEventListener('change', function () {
        blocoOpcoes.style.display = selectTipo.value === 'lista_opcoes' ? 'block' : 'none';
    });
})();
</script>
