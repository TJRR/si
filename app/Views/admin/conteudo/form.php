<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Conteudo do site publico</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>

<form method="post" action="<?php echo url('conteudo/index'); ?>">
    <?php foreach ($conteudos as $conteudo): ?>
        <p>
            <label>
                <?php echo htmlspecialchars($conteudo['rotulo'], ENT_QUOTES, 'UTF-8'); ?><br>
                <?php if ($conteudo['tipo'] === 'texto_longo'): ?>
                    <textarea name="conteudo[<?php echo htmlspecialchars($conteudo['chave'], ENT_QUOTES, 'UTF-8'); ?>]"
                              rows="4" cols="60"><?php echo htmlspecialchars((string) $conteudo['valor'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php else: ?>
                    <input type="text" name="conteudo[<?php echo htmlspecialchars($conteudo['chave'], ENT_QUOTES, 'UTF-8'); ?>]"
                           value="<?php echo htmlspecialchars((string) $conteudo['valor'], ENT_QUOTES, 'UTF-8'); ?>" size="60">
                <?php endif; ?>
            </label>
        </p>
    <?php endforeach; ?>
    <button type="submit">Salvar</button>
</form>
