<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $bloco === null ? 'Novo bloco' : 'Editar bloco'; ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $bloco === null ? url('blocos/novo') : url('blocos/editar/' . (int) $bloco['id']); ?>" enctype="multipart/form-data">
    <label>Título:
        <input type="text" name="titulo" required value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Âncora da seção (usada no menu/scrollspy, sem espaços):
        <input type="text" name="secao_ancora" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['secao_ancora'] : '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($bloco !== null && $bloco['chave'] !== null) ? 'readonly' : ''; ?>>
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

    <fieldset>
        <legend>Imagem (opcional)</legend>
        <label>Imagem:
            <input type="file" name="imagem" accept="image/*">
        </label><br>
        <?php if ($bloco !== null && !empty($bloco['imagem_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $bloco['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:220px;display:block;margin:.5rem 0;">
        <?php endif; ?>
        <label>Texto alternativo da imagem (obrigatório se houver imagem):
            <input type="text" name="imagem_alt" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['imagem_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Posição da imagem em relação ao texto (telas maiores):
            <select name="imagem_posicao">
                <?php $posicoesImagem = ['esquerda' => 'Esquerda', 'direita' => 'Direita']; ?>
                <?php foreach ($posicoesImagem as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($bloco !== null ? $bloco['imagem_posicao'] : 'esquerda') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend>Botão (CTA, opcional)</legend>
        <label>Título do botão:
            <input type="text" name="cta_titulo" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cta_titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Link do botão:
            <input type="text" name="cta_link" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cta_link'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Alinhamento do botão:
            <select name="cta_alinhamento">
                <?php $alinhamentosCta = ['esquerda' => 'Esquerda', 'centro' => 'Centro', 'direita' => 'Direita']; ?>
                <?php foreach ($alinhamentosCta as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($bloco !== null ? $bloco['cta_alinhamento'] : 'esquerda') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($bloco === null || $bloco['ativo']) ? 'checked' : ''; ?>>
        Ativo (visível na home)
    </label>

    <?php if ($bloco === null || $bloco['chave'] === null): ?>
        <br>
        <label>
            <input type="checkbox" name="mostrar_no_menu" value="1" <?php echo ($bloco !== null && $bloco['mostrar_no_menu']) ? 'checked' : ''; ?>>
            Adicionar atalho no menu superior
        </label>
    <?php endif; ?>

    <br>
    <label>
        <input type="checkbox" name="mostrar_no_rodape" value="1" <?php echo ($bloco === null || $bloco['mostrar_no_rodape']) ? 'checked' : ''; ?>>
        Mostrar no rodapé (coluna "Navegação")
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('blocos/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
