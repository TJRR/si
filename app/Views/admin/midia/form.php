<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Nova mídia</h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('midia/novo'); ?>" enctype="multipart/form-data">
    <label>Tipo:
        <select name="tipo" required>
            <option value="imagem">Imagem</option>
            <option value="pdf">PDF</option>
            <option value="video">Vídeo (MP4)</option>
        </select>
    </label><br>

    <label>Arquivo:
        <input type="file" name="arquivo" required>
    </label><br>

    <label>Texto alternativo (obrigatório para imagens):
        <input type="text" name="alt_text">
    </label><br>

    <label>Título (opcional):
        <input type="text" name="titulo">
    </label><br>

    <label>Descrição (opcional):<br>
        <textarea name="descricao" rows="3" cols="50"></textarea>
    </label><br>

    <label>Origem/edição (opcional, só para filtro):
        <select name="concurso_id">
            <option value="">— Não vinculada —</option>
            <?php foreach ($concursos as $concurso): ?>
                <option value="<?php echo (int) $concurso['id']; ?>"><?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('midia/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Enviar</button>
    </div>
</form>
