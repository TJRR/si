<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $banner === null ? 'Nova faixa: ' : 'Editar faixa: '; ?><?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $banner === null ? url('eventoBanners/novo/' . (int) $evento['id']) : url('eventoBanners/editar/' . (int) $banner['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <fieldset>
        <legend>Imagem de fundo (opcional; sem imagem, a cor de fundo prevalece)</legend>

        <label>Imagem (1440×400; o sistema gera a versão para celular automaticamente):
            <input type="file" name="imagem" accept="image/*">
        </label><br>
        <?php if ($banner !== null && !empty($banner['imagem_desktop_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $banner['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:260px;display:block;margin:.5rem 0;">
        <?php endif; ?>

        <label>Texto alternativo da imagem (obrigatório se houver imagem):
            <input type="text" name="imagem_alt" value="<?php echo htmlspecialchars($banner !== null ? (string) $banner['imagem_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <?php $nome = 'cor_fundo'; $valor = $banner !== null ? $banner['cor_fundo'] : null; $rotulo = 'Cor de fundo (usada quando não há imagem)'; $padrao = '#191919'; ?>
        <?php include __DIR__ . '/../_campo_cor.php'; ?>
    </fieldset>

    <fieldset>
        <legend>Texto sobreposto</legend>
        <?php
        $nome = 'conteudo_html';
        $valor = $banner !== null ? (string) $banner['conteudo_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <?php $nome = 'cor_texto'; $valor = $faixa !== null && isset($faixa['cor_texto']) ? $faixa['cor_texto'] : null; $rotulo = 'Cor do texto (sem cor, o texto sai branco)'; $padrao = '#141413'; ?>
        <?php include __DIR__ . '/../_campo_cor.php'; ?>

        <label>Formato:
            <select name="formato">
                <?php $formatos = ['retangulo' => 'Retângulo (faixa de borda a borda)', 'bandeirinha' => 'Balão de diálogo (sobre fundo branco)']; ?>
                <?php foreach ($formatos as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($faixa !== null && isset($faixa['formato']) ? $faixa['formato'] : 'retangulo') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p style="color:#555;font-size:0.9em;">No formato balão de diálogo, o primeiro parágrafo do texto vira o balão da identidade visual (borda escura, ponta no canto superior direito e sombra escura), na cor de fundo escolhida acima, e os demais parágrafos aparecem abaixo dele, em letra menor e discreta.</p>

        <label>Alinhamento do conteúdo:
            <select name="conteudo_alinhamento">
                <?php $alinhamentos = ['esquerda' => 'Esquerda', 'centro' => 'Centro', 'direita' => 'Direita']; ?>
                <?php foreach ($alinhamentos as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($banner !== null ? $banner['conteudo_alinhamento'] : 'centro') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend>Botão de ação</legend>

        <label>Título do botão:
            <input type="text" name="cta_titulo" value="<?php echo htmlspecialchars($banner !== null ? (string) $banner['cta_titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <label>Destino:
            <select name="cta_destino_tipo">
                <option value="">Sem destino</option>
                <?php $destinos = ['link_interno' => 'Hiperlink interno', 'externo' => 'Hiperlink externo', 'ancora' => 'Âncora da página', 'arquivo' => 'Arquivo', 'video' => 'Vídeo']; ?>
                <?php foreach ($destinos as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo ($banner !== null && $banner['cta_destino_tipo'] === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Valor do destino (endereço, âncora, etc.):
            <input type="text" name="cta_destino_valor" value="<?php echo htmlspecialchars($banner !== null ? (string) $banner['cta_destino_valor'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <?php
        $nome = 'cta_posicao';
        $valor = $banner !== null ? $banner['cta_posicao'] : 'centro_centro';
        $rotulo = 'Posição do botão';
        include __DIR__ . '/../_campo_posicao.php';
        ?>

        <label>Efeito ao passar o mouse:
            <select name="cta_efeito_hover">
                <?php $efeitos = ['nenhum' => 'Nenhum', 'escurecer' => 'Escurecer', 'clarear' => 'Clarear', 'escala' => 'Escala', 'borda' => 'Borda', 'iluminar' => 'Iluminar', 'inverter' => 'Inverter']; ?>
                <?php foreach ($efeitos as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($banner !== null ? $banner['cta_efeito_hover'] : 'nenhum') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($banner === null || $banner['ativo']) ? 'checked' : ''; ?>>
        Ativo (visível na página)
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('eventoBanners/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
