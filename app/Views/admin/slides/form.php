<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $slide === null ? 'Novo slide' : 'Editar slide'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $slide === null ? url('slides/novo/' . (int) $concurso['id']) : url('slides/editar/' . (int) $slide['id']); ?>" enctype="multipart/form-data">
    <fieldset>
        <legend>Imagens</legend>

        <label>Imagem desktop (1440×800) <?php echo $slide === null ? '(obrigatória)' : '(deixe em branco para manter a atual)'; ?>:
            <input type="file" name="imagem_desktop" accept="image/*">
        </label><br>
        <?php if ($slide !== null && !empty($slide['imagem_desktop_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $slide['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:220px;display:block;margin:.5rem 0;">
        <?php endif; ?>

        <label>Imagem mobile (768×800, opcional):
            <input type="file" name="imagem_mobile" accept="image/*">
        </label><br>
        <?php if ($slide !== null && !empty($slide['imagem_mobile_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $slide['imagem_mobile_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:140px;display:block;margin:.5rem 0;">
        <?php endif; ?>

        <label>Texto alternativo da imagem (obrigatório):
            <input type="text" name="imagem_alt" required value="<?php echo htmlspecialchars($slide !== null ? (string) $slide['imagem_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Título</legend>
        <?php
        $nome = 'titulo_html';
        $valor = $slide !== null ? (string) $slide['titulo_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <label>Cor do separador abaixo do título:
            <input type="color" name="separador_cor" value="<?php echo htmlspecialchars($slide !== null && $slide['separador_cor'] ? $slide['separador_cor'] : '#F38123', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Botão (CTA)</legend>

        <label>Título do botão:
            <input type="text" name="cta_titulo" value="<?php echo htmlspecialchars($slide !== null ? (string) $slide['cta_titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <label>Link do botão:
            <input type="text" name="cta_link" value="<?php echo htmlspecialchars($slide !== null ? (string) $slide['cta_link'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <label>Abrir em:
            <select name="cta_target">
                <option value="_self" <?php echo ($slide === null || $slide['cta_target'] === '_self') ? 'selected' : ''; ?>>Mesma aba</option>
                <option value="_blank" <?php echo ($slide !== null && $slide['cta_target'] === '_blank') ? 'selected' : ''; ?>>Nova aba</option>
            </select>
        </label><br>

        <label>Cor de fundo:
            <input type="color" name="cta_cor_fundo" value="<?php echo htmlspecialchars($slide !== null && $slide['cta_cor_fundo'] ? $slide['cta_cor_fundo'] : '#FF6600', ENT_QUOTES, 'UTF-8'); ?>">
        </label>

        <label>Cor do texto:
            <input type="color" name="cta_cor_texto" value="<?php echo htmlspecialchars($slide !== null && $slide['cta_cor_texto'] ? $slide['cta_cor_texto'] : '#FFFFFF', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <label>Tamanho:
            <select name="cta_tamanho">
                <?php foreach (['pequeno' => 'Pequeno', 'medio' => 'Médio', 'grande' => 'Grande'] as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($slide !== null ? $slide['cta_tamanho'] : 'medio') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Efeito ao passar o mouse:
            <select name="cta_efeito_hover">
                <?php $efeitos = ['nenhum' => 'Nenhum', 'escurecer' => 'Escurecer', 'clarear' => 'Clarear', 'escala' => 'Escala', 'borda' => 'Borda', 'iluminar' => 'Iluminar', 'inverter' => 'Inverter']; ?>
                <?php foreach ($efeitos as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($slide !== null ? $slide['cta_efeito_hover'] : 'nenhum') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Animação de entrada (nome livre, ex.: "fade-up"):
            <input type="text" name="cta_animacao_entrada" value="<?php echo htmlspecialchars($slide !== null ? (string) $slide['cta_animacao_entrada'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($slide === null || $slide['ativo']) ? 'checked' : ''; ?>>
        Ativo (visível na home)
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('slides/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
