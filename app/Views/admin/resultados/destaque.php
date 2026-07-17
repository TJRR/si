<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Destaque do case — <?php echo htmlspecialchars($resultado['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p><?php echo (int) $resultado['colocacao']; ?>º lugar — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('resultados/editarDestaque/' . (int) $resultado['id']); ?>" enctype="multipart/form-data">
    <label>Resumo de destaque (texto curto, pensado para leitura pública — ex.: "Edições Anteriores"):<br>
        <textarea name="resumo_destaque" rows="5" cols="60"><?php echo htmlspecialchars((string) $resultado['resumo_destaque'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Imagem de destaque (opcional):
        <input type="file" name="imagem_destaque" accept="image/*">
    </label><br>
    <?php if (!empty($resultado['imagem_destaque_path'])): ?>
        <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $resultado['imagem_destaque_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:220px;display:block;margin:.5rem 0;">
    <?php endif; ?>

    <label>Texto alternativo da imagem (obrigatório se houver imagem):
        <input type="text" name="imagem_destaque_alt" value="<?php echo htmlspecialchars((string) $resultado['imagem_destaque_alt'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('apuracao/index/' . (int) $trilha['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
