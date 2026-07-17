<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $banner === null ? 'Novo banner' : 'Editar banner'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $banner === null ? url('banners/novo/' . (int) $concurso['id']) : url('banners/editar/' . (int) $banner['id']); ?>" enctype="multipart/form-data">
    <fieldset>
        <legend>Imagem de fundo (opcional — sem imagem, a cor de fundo prevalece)</legend>

        <label>Imagem desktop (1440×400):
            <input type="file" name="imagem_desktop" accept="image/*">
        </label><br>
        <?php if ($banner !== null && !empty($banner['imagem_desktop_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $banner['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:260px;display:block;margin:.5rem 0;">
        <?php endif; ?>

        <label>Imagem mobile (768×400):
            <input type="file" name="imagem_mobile" accept="image/*">
        </label><br>

        <label>Texto alternativo da imagem (obrigatório se houver imagem):
            <input type="text" name="imagem_alt" value="<?php echo htmlspecialchars($banner !== null ? (string) $banner['imagem_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <label>Cor de fundo (usada quando não há imagem):
            <input type="color" name="cor_fundo" value="<?php echo htmlspecialchars($banner !== null && $banner['cor_fundo'] ? $banner['cor_fundo'] : '#191919', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Texto sobreposto</legend>
        <?php
        $nome = 'conteudo_html';
        $valor = $banner !== null ? (string) $banner['conteudo_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>

    <fieldset>
        <legend>Botão (CTA)</legend>

        <label>Título do botão:
            <input type="text" name="cta_titulo" value="<?php echo htmlspecialchars($banner !== null ? (string) $banner['cta_titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <label>Destino:
            <select name="cta_destino_tipo">
                <option value="">— Sem destino —</option>
                <?php $destinos = ['link_interno' => 'Link interno', 'externo' => 'Link externo', 'ancora' => 'Âncora da página', 'arquivo' => 'Arquivo', 'video' => 'Vídeo']; ?>
                <?php foreach ($destinos as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo ($banner !== null && $banner['cta_destino_tipo'] === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Valor do destino (URL, âncora, etc.):
            <input type="text" name="cta_destino_valor" value="<?php echo htmlspecialchars($banner !== null ? (string) $banner['cta_destino_valor'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>

        <label>Posição do botão:
            <select name="cta_posicao">
                <?php
                $posicoes = [
                    'superior_esquerda' => 'Superior esquerda', 'superior_centro' => 'Superior centro', 'superior_direita' => 'Superior direita',
                    'centro_esquerda' => 'Centro esquerda', 'centro_centro' => 'Centro', 'centro_direita' => 'Centro direita',
                    'inferior_esquerda' => 'Inferior esquerda', 'inferior_centro' => 'Inferior centro', 'inferior_direita' => 'Inferior direita',
                ];
                ?>
                <?php foreach ($posicoes as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($banner !== null ? $banner['cta_posicao'] : 'centro_centro') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>

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
        Ativo (visível na home)
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('banners/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
