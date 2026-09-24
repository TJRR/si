<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $bloco === null ? 'Novo bloco: ' : 'Editar bloco: '; ?><?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $bloco === null ? url('eventoBlocos/novo/' . (int) $evento['id']) : url('eventoBlocos/editar/' . (int) $bloco['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <label>Título:
        <input type="text" name="titulo" required value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Etiqueta acima do título (opcional, texto curto em caixa alta):
        <input type="text" name="etiqueta" maxlength="60" placeholder="GARANTA SUA VAGA" value="<?php echo htmlspecialchars($bloco !== null && isset($bloco['etiqueta']) ? (string) $bloco['etiqueta'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>
    <label>Cor da etiqueta:
        <input type="text" name="etiqueta_cor" maxlength="7" placeholder="#cbd744" value="<?php echo htmlspecialchars($bloco !== null && isset($bloco['etiqueta_cor']) ? (string) $bloco['etiqueta_cor'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Âncora da seção (usada no menu/navegação por rolagem, sem espaços):
        <input type="text" name="secao_ancora" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['secao_ancora'] : '', ENT_QUOTES, 'UTF-8'); ?>">
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
        <legend>Cores da seção (opcional)</legend>
        <p style="color:#555;font-size:0.9em;">Sem cor definida, o bloco segue o padrão da página, alternando fundo claro e fundo levemente cinza.</p>
        <label>Cor de fundo:
            <input type="text" name="cor_fundo" maxlength="7" placeholder="#cbd744" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cor_fundo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Cor do texto:
            <input type="text" name="cor_texto" maxlength="7" placeholder="#141413" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cor_texto'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>
            <input type="checkbox" name="usar_cor_rodape" value="1" <?php echo ($bloco !== null && !empty($bloco['usar_cor_rodape'])) ? 'checked' : ''; ?>>
            Usar a cor de fundo do rodapé e encostar o bloco nele (ideal para a chamada final, logo antes do rodapé)
        </label>
    </fieldset>

    <fieldset>
        <legend>Botão de ação (opcional)</legend>
        <label>Título do botão:
            <input type="text" name="cta_titulo" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cta_titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Hiperlink do botão:
            <input type="text" name="cta_link" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cta_link'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Cor de fundo do botão:
            <input type="text" name="cta_cor_fundo" maxlength="7" placeholder="#ea5a43" value="<?php echo htmlspecialchars($bloco !== null && isset($bloco['cta_cor_fundo']) ? (string) $bloco['cta_cor_fundo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Cor do texto do botão:
            <input type="text" name="cta_cor_texto" maxlength="7" placeholder="#141413" value="<?php echo htmlspecialchars($bloco !== null && isset($bloco['cta_cor_texto']) ? (string) $bloco['cta_cor_texto'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Alinhamento dos botões:
            <select name="cta_alinhamento">
                <?php $alinhamentosCta = ['esquerda' => 'Esquerda', 'centro' => 'Centro', 'direita' => 'Direita']; ?>
                <?php foreach ($alinhamentosCta as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo (($bloco !== null ? $bloco['cta_alinhamento'] : 'esquerda') === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    
        <hr>
        <label>Título do segundo botão:
            <input type="text" name="cta2_titulo" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cta2_titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Hiperlink do segundo botão:
            <input type="text" name="cta2_link" value="<?php echo htmlspecialchars($bloco !== null ? (string) $bloco['cta2_link'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Cor de fundo do segundo botão (em branco: só contorno):
            <input type="text" name="cta2_cor_fundo" maxlength="7" value="<?php echo htmlspecialchars($bloco !== null && isset($bloco['cta2_cor_fundo']) ? (string) $bloco['cta2_cor_fundo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
        <label>Cor do texto do segundo botão:
            <input type="text" name="cta2_cor_texto" maxlength="7" value="<?php echo htmlspecialchars($bloco !== null && isset($bloco['cta2_cor_texto']) ? (string) $bloco['cta2_cor_texto'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">Destinos aceitos nos botões: âncora da própria página ("#programacao"), endereço completo ("https://...") ou endereço interno do sistema ("eventoInscricao/index/<?php echo (int) $evento['id']; ?>").</p>
    </fieldset>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($bloco === null || $bloco['ativo']) ? 'checked' : ''; ?>>
        Ativo (visível na página)
    </label>

    <br>
    <p style="color:#555;font-size:0.9em;">A posição deste bloco na página e o atalho dele no menu superior ficam em <a href="<?php echo url('eventoSecoes/index/' . (int) $evento['id']); ?>">Seções da página</a>, junto com as demais seções do evento.</p>

    <div class="form-acoes">
        <a href="<?php echo url('eventoBlocos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
