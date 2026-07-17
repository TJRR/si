<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $faq === null ? 'Nova pergunta' : 'Editar pergunta'; ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $faq === null ? url('faq/novo') : url('faq/editar/' . (int) $faq['id']); ?>">
    <label>Categoria (ex.: Inscrição, Avaliação, Premiação):
        <input type="text" name="categoria" value="<?php echo htmlspecialchars($faq !== null ? (string) $faq['categoria'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Pergunta:
        <input type="text" name="pergunta" required value="<?php echo htmlspecialchars($faq !== null ? $faq['pergunta'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Resposta:<br>
        <textarea name="resposta" rows="5" cols="60" required><?php echo htmlspecialchars($faq !== null ? $faq['resposta'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('faq/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
