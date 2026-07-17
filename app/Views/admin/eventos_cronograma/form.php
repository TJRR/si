<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $evento === null ? 'Novo evento' : 'Editar evento'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php
$paraInputDatetime = function ($valor) {
    return $valor !== null ? date('Y-m-d\TH:i', strtotime($valor)) : '';
};
?>
<form method="post" action="<?php echo $evento === null ? url('eventosCronograma/novo/' . (int) $concurso['id']) : url('eventosCronograma/editar/' . (int) $evento['id']); ?>">
    <label>Título:
        <input type="text" name="titulo" required value="<?php echo htmlspecialchars($evento !== null ? $evento['titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Descrição:<br>
        <textarea name="descricao" rows="4" cols="50"><?php echo htmlspecialchars($evento !== null ? (string) $evento['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Início:
        <input type="datetime-local" name="data_inicio" required value="<?php echo $evento !== null ? $paraInputDatetime($evento['data_inicio']) : ''; ?>">
    </label><br>

    <label>Fim (opcional):
        <input type="datetime-local" name="data_fim" value="<?php echo $evento !== null ? $paraInputDatetime($evento['data_fim']) : ''; ?>">
    </label><br>

    <label>Vincular a uma Etapa real (opcional):
        <select name="etapa_id">
            <option value="">— Nenhuma —</option>
            <?php foreach ($etapasDisponiveis as $etapa): ?>
                <option value="<?php echo (int) $etapa['id']; ?>" <?php echo ($evento !== null && (int) $evento['etapa_id'] === (int) $etapa['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($etapa['trilha_nome'] . ' — ' . $etapa['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('eventosCronograma/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
