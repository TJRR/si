<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $premio === null ? 'Novo prêmio' : 'Editar prêmio'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $premio === null ? url('premios/novo/' . (int) $concurso['id']) : url('premios/editar/' . (int) $premio['id']); ?>" enctype="multipart/form-data">
    <label>Posição (1, 2, 3...):
        <input type="number" name="posicao" min="1" required value="<?php echo $premio !== null ? (int) $premio['posicao'] : ''; ?>">
    </label><br>

    <label>Descrição:<br>
        <textarea name="descricao" rows="4" cols="50" required><?php echo htmlspecialchars($premio !== null ? (string) $premio['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Imagem/ícone (opcional):
        <input type="file" name="imagem" accept="image/*">
    </label><br>
    <?php if ($premio !== null && !empty($premio['imagem_path'])): ?>
        <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $premio['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:120px;display:block;margin:.5rem 0;">
    <?php endif; ?>

    <label>Texto alternativo da imagem (obrigatório se houver imagem):
        <input type="text" name="imagem_alt" value="<?php echo htmlspecialchars($premio !== null ? (string) $premio['imagem_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('premios/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
