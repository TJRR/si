<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Registrar dúvida</h1>
    <a href="<?php echo url('duvida/index'); ?>" class="btn-voltar">Voltar</a>
</div>

<p>Descreva uma única dúvida por registro — isso ajuda a direcionar sua pergunta pra quem
pode responder mais rápido.</p>

<?php if ($erro !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Sua dúvida:<br>
        <textarea name="pergunta" rows="6" style="width:100%;max-width:640px;" required><?php echo htmlspecialchars(isset($_POST['pergunta']) ? $_POST['pergunta'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br><br>

    <label>Anexo (opcional — PDF, WEBP, JPG, JPEG ou PNG, até <?php echo $limiteMB; ?>MB):
        <input type="file" name="anexo" accept="application/pdf,image/webp,image/jpeg,image/png">
    </label>

    <div class="form-acoes">
        <button type="submit" class="btn-acao">Registrar</button>
    </div>
</form>
