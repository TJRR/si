<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $slide === null ? 'Novo slide' : 'Editar slide'; ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $slide === null ? url('slides/novo') : url('slides/editar/' . (int) $slide['id']); ?>" enctype="multipart/form-data">
    <fieldset>
        <legend>Imagem</legend>

        <label>Imagem de fundo (opcional — 1440×800; o sistema gera a versão mobile automaticamente) <?php echo $slide !== null ? '(deixe em branco para manter a atual)' : ''; ?>:
            <input type="file" name="imagem" accept="image/*">
        </label><br>
        <?php if ($slide !== null && !empty($slide['imagem_desktop_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $slide['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:220px;display:block;margin:.5rem 0;">
        <?php endif; ?>

        <?php $nome = 'cor_fundo'; $valor = $slide !== null ? $slide['cor_fundo'] : null; $rotulo = 'Cor de fundo (usada quando não há imagem)'; $padrao = '#191919'; ?>
        <?php include __DIR__ . '/../_campo_cor.php'; ?>

        <label>Texto alternativo da imagem (obrigatório só se houver imagem):
            <input type="text" name="imagem_alt" value="<?php echo htmlspecialchars($slide !== null ? (string) $slide['imagem_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Efeito de camada sobre a imagem</legend>
        <p>Só faz efeito quando há imagem de fundo cadastrada acima.</p>

        <label>Efeito:
            <select name="overlay_efeito">
                <?php
                $overlayEfeitos = [
                    'nenhum' => 'Nenhum',
                    'escurecer' => 'Escurecer (gradiente)',
                    'vinheta' => 'Vinheta',
                    'pontos' => 'Pontos',
                    'linhas' => 'Linhas diagonais',
                    'halftone' => 'Pontos vazados',
                    'trama' => 'Trama Scrim',
                ];
                ?>
                <?php foreach ($overlayEfeitos as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($slide !== null ? $slide['overlay_efeito'] : 'nenhum') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Opacidade (%):
            <input type="number" name="overlay_opacidade" min="0" max="100" value="<?php echo $slide !== null ? (int) $slide['overlay_opacidade'] : 40; ?>">
        </label><br>

        <?php $nome = 'overlay_cor'; $valor = $slide !== null ? $slide['overlay_cor'] : null; $rotulo = 'Cor do efeito (padrão preto, ou branco nos efeitos "Pontos"/"Linhas diagonais")'; $padrao = '#000000'; ?>
        <?php include __DIR__ . '/../_campo_cor.php'; ?>
    </fieldset>

    <fieldset>
        <legend>Apresentação</legend>

        <label>Duração deste slide (segundos):
            <input type="number" name="duracao_segundos" min="1" max="30" value="<?php echo $slide !== null ? (int) ($slide['duracao_ms'] / 1000) : 7; ?>">
        </label><br>

        <label>Efeito de transição:
            <select name="efeito_transicao">
                <?php $efeitosTransicao = ['fade' => 'Fade (padrão)', 'slide' => 'Deslizar', 'zoom' => 'Zoom']; ?>
                <?php foreach ($efeitosTransicao as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($slide !== null ? $slide['efeito_transicao'] : 'fade') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
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

        <?php $nome = 'separador_cor'; $valor = $slide !== null ? $slide['separador_cor'] : null; $rotulo = 'Cor do separador abaixo do título'; $padrao = '#F38123'; ?>
        <?php include __DIR__ . '/../_campo_cor.php'; ?>
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

        <?php $nome = 'cta_cor_fundo'; $valor = $slide !== null ? $slide['cta_cor_fundo'] : null; $rotulo = 'Cor de fundo'; $padrao = '#FF6600'; ?>
        <?php include __DIR__ . '/../_campo_cor.php'; ?>

        <?php $nome = 'cta_cor_texto'; $valor = $slide !== null ? $slide['cta_cor_texto'] : null; $rotulo = 'Cor do texto'; $padrao = '#FFFFFF'; ?>
        <?php include __DIR__ . '/../_campo_cor.php'; ?>

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
        <a href="<?php echo url('slides/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
