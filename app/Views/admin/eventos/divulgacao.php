<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Divulgação na home: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p style="color:#555;">
    Bloco de chamada exibido na home pública, fixo entre o carrossel de imagens e as Faixas,
    fora do mecanismo de ordenação por arraste. Se houver mais de um evento
    com divulgação ativa ao mesmo tempo, todos aparecem empilhados, o mais
    recente primeiro.
</p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('eventos/divulgacao/' . (int) $evento['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <label>Título:
        <input type="text" name="titulo" value="<?php echo htmlspecialchars($divulgacao !== null ? (string) $divulgacao['titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" size="60">
    </label><br>

    <fieldset>
        <legend>Conteúdo</legend>
        <?php
        $nome = 'conteudo_html';
        $valor = $divulgacao !== null ? (string) $divulgacao['conteudo_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>

    <fieldset>
        <legend>Imagem (opcional)</legend>
        <label>Imagem:
            <input type="file" name="imagem" accept="image/*">
        </label><br>
        <?php if ($divulgacao !== null && !empty($divulgacao['imagem_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $divulgacao['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:220px;display:block;margin:.5rem 0;">
        <?php endif; ?>
        <label>Texto alternativo da imagem (obrigatório se houver imagem):
            <input type="text" name="imagem_alt" value="<?php echo htmlspecialchars($divulgacao !== null ? (string) $divulgacao['imagem_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Posição da imagem em relação ao texto (telas maiores):
            <select name="imagem_posicao">
                <?php $posicoesImagem = ['esquerda' => 'Esquerda', 'direita' => 'Direita']; ?>
                <?php foreach ($posicoesImagem as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($divulgacao !== null ? $divulgacao['imagem_posicao'] : 'esquerda') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend>Botão de chamada (opcional)</legend>
        <label>Texto do botão:
            <input type="text" name="cta_titulo" value="<?php echo htmlspecialchars($divulgacao !== null ? (string) $divulgacao['cta_titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Inscreva-se">
        </label><br>
        <label>Alinhamento do botão:
            <select name="cta_alinhamento">
                <?php $alinhamentosCta = ['esquerda' => 'Esquerda', 'centro' => 'Centro', 'direita' => 'Direita']; ?>
                <?php foreach ($alinhamentosCta as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($divulgacao !== null ? $divulgacao['cta_alinhamento'] : 'esquerda') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p style="color:#555;font-size:0.9em;">Este botão sempre leva direto à inscrição deste evento.</p>
    </fieldset>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($divulgacao !== null && $divulgacao['ativo']) ? 'checked' : ''; ?>>
        Ativo (visível na home)
    </label>
    <p style="color:#555;font-size:0.9em;">Só aparece na home se estiver ativo e com o título preenchido.</p>

    <div class="form-acoes">
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
