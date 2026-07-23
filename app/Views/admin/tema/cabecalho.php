<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Cabeçalho</h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-cabecalho">Salvar</button>
    </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('tema/cabecalho'); ?>" enctype="multipart/form-data" id="form-cabecalho">
    <fieldset>
        <legend>Logo padrão do sistema</legend>
        <?php if (!empty($configuracaoVisual['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['logo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo atual" style="max-width:200px;display:block;margin-bottom:.5rem;">
        <?php endif; ?>
        <label>
            Trocar logo:<br>
            <input type="file" name="logo" accept="image/*">
        </label>
    </fieldset>

    <fieldset>
        <legend>Imagem de fundo do cabeçalho (opcional)</legend>
        <p>Se enviada, o cabeçalho aparece alto, com esta imagem de fundo, transparente sobre ela e vira sólido ao rolar a página. Sem imagem, o cabeçalho continua uma barra fina sólida, igual sempre foi.</p>
        <?php if (!empty($configuracaoVisual['cabecalho_imagem_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['cabecalho_imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Imagem atual do cabeçalho" style="max-width:320px;display:block;margin-bottom:.5rem;">
        <?php endif; ?>
        <label>
            Trocar imagem de fundo:<br>
            <input type="file" name="cabecalho_imagem" accept="image/*">
        </label>
        <br>
        <?php
        $nome = 'cabecalho_imagem_posicao';
        $valor = $configuracaoVisual !== false ? $configuracaoVisual['cabecalho_imagem_posicao'] : 'superior_centro';
        $rotulo = 'Posição da imagem de fundo';
        include __DIR__ . '/../_campo_posicao.php';
        ?>
    </fieldset>

    <fieldset>
        <legend>Efeito visual do cabeçalho (só faz efeito com imagem de fundo)</legend>
        <label>
            Transição na base do cabeçalho:<br>
            <select name="cabecalho_efeito_transicao">
                <?php $efeitosTransicao = ['onda' => 'Onda', 'diagonal_esquerda' => 'Diagonal para esquerda', 'diagonal_direita' => 'Diagonal para direita']; ?>
                <?php foreach ($efeitosTransicao as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($configuracaoVisual !== false && $configuracaoVisual['cabecalho_efeito_transicao'] ? $configuracaoVisual['cabecalho_efeito_transicao'] : 'onda') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>
            Opacidade do tom de cor sobre a imagem (%):<br>
            <input type="number" name="cabecalho_overlay_opacidade" min="0" max="100" value="<?php echo $configuracaoVisual !== false ? (int) $configuracaoVisual['cabecalho_overlay_opacidade'] : 50; ?>">
        </label><br>
        <label>
            Efeito de entrada do título ao carregar a página:<br>
            <select name="cabecalho_efeito_entrada">
                <?php $efeitosEntrada = ['nenhum' => 'Nenhum', 'fade' => 'Aparecer suavemente (fade)', 'subir' => 'Subir suavemente', 'zoom' => 'Aproximar (zoom)']; ?>
                <?php foreach ($efeitosEntrada as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($configuracaoVisual !== false && $configuracaoVisual['cabecalho_efeito_entrada'] ? $configuracaoVisual['cabecalho_efeito_entrada'] : 'nenhum') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend>Logo clara — usada sobre a imagem de fundo (opcional)</legend>
        <p>Só faz efeito se a imagem de fundo acima estiver preenchida. Sem uma logo clara enviada, a logo normal acima é usada mesmo sobre a imagem.</p>
        <?php if (!empty($configuracaoVisual['cabecalho_logo_claro_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['cabecalho_logo_claro_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo clara atual" style="max-width:200px;display:block;margin-bottom:.5rem;background:#333;padding:.5rem;">
        <?php endif; ?>
        <label>
            Trocar logo clara:<br>
            <input type="file" name="logo_claro" accept="image/*">
        </label>
    </fieldset>

    <fieldset>
        <legend>Título do cabeçalho (opcional — aparece só quando há imagem de fundo)</legend>
        <p>Escreva o título/slogan do jeito que quiser. Dá pra inserir imagens usando o botão de imagem da barra abaixo — qualquer imagem inserida aqui ganha um efeito de "boiar" suave automaticamente na home.</p>
        <?php
        $nome = 'cabecalho_titulo_html';
        $valor = $configuracaoVisual !== false ? (string) $configuracaoVisual['cabecalho_titulo_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>
</form>
