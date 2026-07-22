<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Editar documento — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p><small>Aqui só é possível corrigir Tipo, Trilha e Título — para trocar o arquivo, use "+ Novo documento" (vira uma nova versão).</small></p>

<form method="post" action="<?php echo url('documentos/editar/' . (int) $documento['id']); ?>">
    <label>Tipo:
        <select name="tipo" required>
            <?php $rotulosTipo = ['edital' => 'Edital', 'edital_simples' => 'Edital em linguagem simples', 'anexo' => 'Anexo', 'retificacao' => 'Retificação', 'resultado_final' => 'Resultado final', 'ata' => 'Ata']; ?>
            <?php foreach ($rotulosTipo as $valorOpcao => $rotuloOpcao): ?>
                <option value="<?php echo $valorOpcao; ?>" <?php echo $documento['tipo'] === $valorOpcao ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Trilha (opcional — deixe em branco se o documento vale para todo o concurso):
        <select name="trilha_id">
            <option value="">— Todo o concurso —</option>
            <?php foreach ($trilhas as $trilha): ?>
                <option value="<?php echo (int) $trilha['id']; ?>" <?php echo (int) $documento['trilha_id'] === (int) $trilha['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Título:
        <input type="text" name="titulo" required value="<?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('documentos/index/' . (int) $documento['concurso_id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
