<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Editar declaração: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/termos/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Alterar o texto aqui vale só para as próximas submissões: o que já foi aceito fica guardado como estava no dia do envio.</p>

<form method="post" action="<?php echo url('trabalhos/termoEditar/' . (int) $termo['id']); ?>"><?= campoCsrf() ?>
    <label>Identificação interna: <input type="text" name="rotulo" required maxlength="150" value="<?php echo htmlspecialchars($termo['rotulo'], ENT_QUOTES, 'UTF-8'); ?>"></label>

    <fieldset>
        <legend>Texto que a pessoa lê e aceita</legend>
        <?php
        $nome = 'texto_html';
        $valor = (string) $termo['texto_html'];
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>

    <label><input type="checkbox" name="obrigatorio" value="1" <?php echo (int) $termo['obrigatorio'] === 1 ? 'checked' : ''; ?>> Aceite obrigatório para enviar o trabalho</label>
    <label><input type="checkbox" name="ativo" value="1" <?php echo (int) $termo['ativo'] === 1 ? 'checked' : ''; ?>> Ativa (aparece no formulário)</label>

    <button type="submit">Salvar declaração</button>
</form>
