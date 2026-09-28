<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Atualizar estande</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('representanteEstande/estande/' . (int) $estande['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($erro)): ?>
    <p class="flash-mensagem erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">O que você salvar aqui aparece na hora para os participantes, no aplicativo e na página do evento. Código, pontuação, categoria e situação do estande são definidos pela organização do evento.</p>

<form method="post" action="<?php echo url('representanteEstande/editar/' . (int) $estande['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <label>Nome do estande: *
        <input type="text" name="nome" maxlength="150" required value="<?php echo htmlspecialchars((string) $estande['nome'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <fieldset>
        <legend>Descrição (opcional)</legend>
        <?php
        $nome = 'descricao_html';
        $valor = (string) $estande['descricao_html'];
        $rotulo = null;
        $semImagem = true;
        include __DIR__ . '/../admin/_editor_rico.php';
        unset($semImagem);
        ?>
    </fieldset>

    <fieldset>
        <legend>Logotipo (opcional)</legend>
        <?php if (!empty($estande['logotipo_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $estande['logotipo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:240px;max-height:120px;display:block;margin:.5rem 0;">
        <?php endif; ?>
        <label>Imagem (JPG, PNG, WEBP ou GIF, até 4 MB; o sistema reduz para no máximo 600 por 300 pixels):
            <input type="file" name="logotipo" accept="image/*">
        </label>
        <label>Texto alternativo do logotipo (obrigatório se houver logotipo; descreve a imagem para quem usa leitor de tela):
            <input type="text" name="logotipo_alt" maxlength="255" value="<?php echo htmlspecialchars((string) $estande['logotipo_alt'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <div class="form-acoes">
        <a href="<?php echo url('representanteEstande/estande/' . (int) $estande['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
