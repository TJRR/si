<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Cabeçalho: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-cabecalho-evento">Salvar</button>
    </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('eventoCabecalho/cabecalho/' . (int) $evento['id']); ?>" enctype="multipart/form-data" id="form-cabecalho-evento"><?= campoCsrf() ?>
    <fieldset>
        <legend>Publicação</legend>
        <p>Enquanto esta caixa não estiver marcada, a página pública deste evento (endereço abaixo) responde como não encontrada para qualquer pessoa.</p>
        <label>
            <input type="checkbox" name="publicado" value="1" <?php echo (!empty($configuracaoVisual['publicado'])) ? 'checked' : ''; ?>>
            Publicar esta página
        </label>
        <?php if (!empty($configuracaoVisual['publicado'])): ?>
            <p>Endereço público: <a href="<?php echo urlAbsoluta('evento/index/' . (int) $evento['id']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(urlAbsoluta('evento/index/' . (int) $evento['id']), ENT_QUOTES, 'UTF-8'); ?></a></p>
        <?php endif; ?>
    </fieldset>

    <fieldset>
        <legend>Logo do evento (opcional)</legend>
        <p>Enviada aqui, esta logo vale na página pública deste evento e no rodapé dela, sem depender do tema de cor que cada pessoa escolheu. Sem logo enviada, continua valendo a logo do tema.</p>
        <?php if (!empty($configuracaoVisual['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['logo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo atual do evento" style="max-width:240px;display:block;margin-bottom:.5rem;">
            <label>
                <input type="checkbox" name="remover_logo" value="1">
                Remover logo do evento
            </label>
            <br>
        <?php endif; ?>
        <label>
            Enviar logo:<br>
            <input type="file" name="logo" accept="image/*">
        </label>
        <br>
        <label>
            Texto alternativo da logo:
            <input type="text" name="logo_alt" maxlength="255" value="<?php echo htmlspecialchars((string) (isset($configuracaoVisual['logo_alt']) ? $configuracaoVisual['logo_alt'] : ''), ENT_QUOTES, 'UTF-8'); ?>" size="40">
        </label>
    </fieldset>

    <fieldset>
        <legend>Fontes da página pública</legend>
        <p>Valem só na página pública deste evento. Sem escolha, a página usa as fontes padrão do sistema.</p>
        <?php $fontesDisponiveis = array_keys(\App\Repositories\EventoConfiguracaoVisualRepository::FONTES); ?>
        <label>
            Fonte dos títulos:
            <select name="fonte_titulo">
                <option value="">Fonte padrão do sistema</option>
                <?php foreach ($fontesDisponiveis as $fonte): ?>
                    <option value="<?php echo htmlspecialchars($fonte, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($configuracaoVisual['fonte_titulo']) && $configuracaoVisual['fonte_titulo'] === $fonte) ? 'selected' : ''; ?> style="font-family:'<?php echo htmlspecialchars($fonte, ENT_QUOTES, 'UTF-8'); ?>'"><?php echo htmlspecialchars($fonte, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <br>
        <label>
            Fonte do texto:
            <select name="fonte_texto">
                <option value="">Fonte padrão do sistema</option>
                <?php foreach ($fontesDisponiveis as $fonte): ?>
                    <option value="<?php echo htmlspecialchars($fonte, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($configuracaoVisual['fonte_texto']) && $configuracaoVisual['fonte_texto'] === $fonte) ? 'selected' : ''; ?>><?php echo htmlspecialchars($fonte, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend>Imagem de fundo do cabeçalho (opcional)</legend>
        <p>Se enviada, o cabeçalho aparece alto, com esta imagem de fundo, transparente sobre ela e vira sólido ao rolar a página. Sem imagem, o cabeçalho continua uma barra fina sólida.</p>
        <?php if (!empty($configuracaoVisual['cabecalho_imagem_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['cabecalho_imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Imagem atual do cabeçalho" style="max-width:320px;display:block;margin-bottom:.5rem;">
            <label>
                <input type="checkbox" name="remover_cabecalho_imagem" value="1">
                Remover imagem de fundo
            </label>
            <br>
        <?php endif; ?>
        <label>
            Trocar imagem de fundo:<br>
            <input type="file" name="cabecalho_imagem" accept="image/*">
        </label>
        <br>
        <?php
        $nome = 'cabecalho_imagem_posicao';
        $valor = $configuracaoVisual !== null ? $configuracaoVisual['cabecalho_imagem_posicao'] : 'superior_centro';
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
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($configuracaoVisual !== null && $configuracaoVisual['cabecalho_efeito_transicao'] ? $configuracaoVisual['cabecalho_efeito_transicao'] : 'onda') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>
            Opacidade do tom de cor sobre a imagem (%):<br>
            <input type="number" name="cabecalho_overlay_opacidade" min="0" max="100" value="<?php echo $configuracaoVisual !== null ? (int) $configuracaoVisual['cabecalho_overlay_opacidade'] : 50; ?>">
        </label><br>
        <label>
            Efeito de entrada do título ao carregar a página:<br>
            <select name="cabecalho_efeito_entrada">
                <?php $efeitosEntrada = ['nenhum' => 'Nenhum', 'fade' => 'Aparecer suavemente (fade)', 'subir' => 'Subir suavemente', 'zoom' => 'Aproximar (zoom)']; ?>
                <?php foreach ($efeitosEntrada as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($configuracaoVisual !== null && $configuracaoVisual['cabecalho_efeito_entrada'] ? $configuracaoVisual['cabecalho_efeito_entrada'] : 'nenhum') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend>Logo clara: usada sobre a imagem de fundo (opcional)</legend>
        <p>Só faz efeito se a imagem de fundo acima estiver preenchida. Sem uma logo clara enviada, a logo normal do evento é usada mesmo sobre a imagem.</p>
        <?php if (!empty($configuracaoVisual['cabecalho_logo_claro_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['cabecalho_logo_claro_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo clara atual" style="max-width:200px;display:block;margin-bottom:.5rem;background:#333;padding:.5rem;">
            <label>
                <input type="checkbox" name="remover_logo_claro" value="1">
                Remover logo clara
            </label>
            <br>
        <?php endif; ?>
        <label>
            Trocar logo clara:<br>
            <input type="file" name="logo_claro" accept="image/*">
        </label>
    </fieldset>

    <fieldset>
        <legend>Título do cabeçalho (opcional: aparece só quando há imagem de fundo)</legend>
        <p>Escreva o título/subtítulo do jeito que quiser. É possível inserir imagens usando o botão de imagem da barra abaixo; qualquer imagem inserida aqui ganha um efeito de "boiar" suave automaticamente na página.</p>
        <?php
        $nome = 'cabecalho_titulo_html';
        $valor = $configuracaoVisual !== null ? (string) $configuracaoVisual['cabecalho_titulo_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>
</form>
