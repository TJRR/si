<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Páginas</h1>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('conteudo/index'); ?>" enctype="multipart/form-data">
    <fieldset>
        <legend>Textos e imagens</legend>
        <?php foreach ($conteudos as $conteudo): ?>
            <p>
                <label>
                    <?php echo htmlspecialchars($conteudo['rotulo'], ENT_QUOTES, 'UTF-8'); ?><br>

                    <?php if ($conteudo['tipo'] === 'texto_longo'): ?>
                        <textarea name="conteudo[<?php echo htmlspecialchars($conteudo['chave'], ENT_QUOTES, 'UTF-8'); ?>]"
                                  rows="4" cols="60"><?php echo htmlspecialchars((string) $conteudo['valor'], ENT_QUOTES, 'UTF-8'); ?></textarea>

                    <?php elseif ($conteudo['tipo'] === 'imagem'): ?>
                        <?php if (!empty($conteudo['arquivo_path'])): ?>
                            <br>
                            <img src="<?php echo config('base_path') . '/assets/' . htmlspecialchars($conteudo['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="" style="max-height:80px; display:block; margin-bottom:0.35rem;">
                            <span>Deixe vazio para manter a imagem atual.</span><br>
                        <?php endif; ?>
                        <input type="file" name="conteudo_imagem[<?php echo htmlspecialchars($conteudo['chave'], ENT_QUOTES, 'UTF-8'); ?>]"
                               accept="image/jpeg,image/png,image/webp,image/gif">

                    <?php else: ?>
                        <input type="text" name="conteudo[<?php echo htmlspecialchars($conteudo['chave'], ENT_QUOTES, 'UTF-8'); ?>]"
                               value="<?php echo htmlspecialchars((string) $conteudo['valor'], ENT_QUOTES, 'UTF-8'); ?>" size="60">
                    <?php endif; ?>
                </label>
            </p>
        <?php endforeach; ?>
    </fieldset>

    <button type="submit">Salvar</button>
</form>
