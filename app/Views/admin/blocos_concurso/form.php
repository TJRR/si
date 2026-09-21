<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Bloco da edição: <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p style="color:#555;font-size:0.9em;">
    Conteúdo opcional exibido na página pública desta edição (Edições Anteriores),
    depois da galeria de fotos. Use para informações peculiares desta edição, como
    uma "Classificação Geral" que não se encaixa nos vencedores por trilha.
</p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('blocoConcurso/index/' . (int) $concurso['id']); ?>"><?= campoCsrf() ?>
    <label>Título da seção (opcional):
        <input type="text" name="titulo" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" size="60">
    </label>

    <fieldset>
        <legend>Conteúdo</legend>
        <?php
        $nome = 'conteudo_html';
        $valor = $bloco !== null ? (string) $bloco['conteudo_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($bloco === null || !empty($bloco['ativo'])) ? 'checked' : ''; ?>>
        Exibir este bloco na página pública desta edição
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('concursos/editar/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
