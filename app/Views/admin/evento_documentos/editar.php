<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Editar documento: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p><small>Aqui só é possível corrigir Tipo e Título: para trocar o arquivo, use "+ Novo documento" com o mesmo tipo e título (vira uma nova versão).</small></p>

<form method="post" action="<?php echo url('eventoDocumentos/editar/' . (int) $evento['id'] . '/' . (int) $documento['id']); ?>"><?= campoCsrf() ?>
    <label>Tipo:
        <select name="tipo" required>
            <?php foreach (\App\Repositories\EventoDocumentoRepository::ROTULOS_TIPO as $valorOpcao => $rotuloOpcao): ?>
                <option value="<?php echo $valorOpcao; ?>" <?php echo $documento['tipo'] === $valorOpcao ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Título:
        <input type="text" name="titulo" required maxlength="200" value="<?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('eventoDocumentos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
