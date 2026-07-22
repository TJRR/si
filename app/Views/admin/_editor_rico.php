<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Partial reaproveitavel do editor de texto rico (Fase 18). Inclusao:
 *   <?php $nome = 'titulo_html'; $valor = $slide['titulo_html']; $rotulo = 'Título'; ?>
 *   <?php include __DIR__ . '/../_editor_rico.php'; ?>
 * $nome e $valor sao obrigatorios; $rotulo e' opcional.
 */
$idEditor = 'editor-' . preg_replace('/[^a-z0-9]/', '', strtolower($nome)) . '-' . mt_rand(1000, 9999);
$valorAtual = isset($valor) ? (string) $valor : '';
?>
<div class="editor-rico">
    <?php if (!empty($rotulo)): ?>
        <label class="editor-rico-rotulo" id="lbl-<?php echo $idEditor; ?>"><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></label>
    <?php endif; ?>
    <div class="editor-rico-caixa" role="group" <?php echo !empty($rotulo) ? 'aria-labelledby="lbl-' . $idEditor . '"' : ''; ?>>
        <div class="editor-rico-barra">
            <button type="button" class="editor-rico-btn" data-comando="negrito" title="Negrito" aria-label="Negrito"><strong>N</strong></button>
            <button type="button" class="editor-rico-btn" data-comando="italico" title="Itálico" aria-label="Itálico"><em>I</em></button>
            <button type="button" class="editor-rico-btn" data-comando="sublinhado" title="Sublinhado" aria-label="Sublinhado"><span style="text-decoration:underline;">S</span></button>
            <button type="button" class="editor-rico-btn" data-comando="riscado" title="Riscado" aria-label="Riscado"><span style="text-decoration:line-through;">R</span></button>
            <button type="button" class="editor-rico-btn" data-comando="subscrito" title="Subscrito" aria-label="Subscrito">x₂</button>
            <button type="button" class="editor-rico-btn" data-comando="sobrescrito" title="Sobrescrito" aria-label="Sobrescrito">x²</button>
            <button type="button" class="editor-rico-btn" data-comando="listaMarcadores" title="Lista com marcadores" aria-label="Lista com marcadores">•≡</button>
            <button type="button" class="editor-rico-btn" data-comando="listaNumerada" title="Lista numerada" aria-label="Lista numerada">1.≡</button>
            <select class="editor-rico-select" data-comando="fonte" title="Fonte" aria-label="Fonte">
                <option value="">Fonte padrão</option>
                <option value="Poppins, sans-serif">Poppins</option>
                <option value="Roboto, sans-serif">Roboto</option>
                <option value="Georgia, serif">Georgia</option>
            </select>
            <input type="number" class="editor-rico-tamanho" data-comando="tamanho" min="8" max="72" placeholder="Tam. (px)" title="Tamanho da fonte em pixels" aria-label="Tamanho da fonte em pixels">
            <select class="editor-rico-select" data-comando="alinhar" title="Alinhamento" aria-label="Alinhamento">
                <option value="left">Esquerda</option>
                <option value="center">Centro</option>
                <option value="right">Direita</option>
                <option value="justify">Justificado</option>
            </select>
            <input type="color" class="editor-rico-cor" data-comando="cor" title="Cor do texto" aria-label="Cor do texto" value="#191919">
            <input type="color" class="editor-rico-cor" data-comando="realce" title="Cor de realce (fundo do texto)" aria-label="Cor de realce do texto" value="#fff3cd">
            <button type="button" class="editor-rico-btn" data-comando="semRealce" title="Remover realce" aria-label="Remover realce">⌫</button>
            <button type="button" class="editor-rico-btn" data-comando="link" title="Inserir link" aria-label="Inserir link">🔗</button>
            <button type="button" class="editor-rico-btn" data-comando="imagem" title="Inserir imagem" aria-label="Inserir imagem">🖼</button>
            <button type="button" class="editor-rico-btn" data-comando="barra" title="Inserir barra separadora (usa a cor de texto selecionada)" aria-label="Inserir barra separadora">▬</button>
            <button type="button" class="editor-rico-btn" data-editor-alternar-modo title="Alternar entre visual e código" aria-label="Alternar entre visual e código">&lt;/&gt;</button>
        </div>
        <div class="editor-rico-area" contenteditable="true" data-editor-area><?php echo $valorAtual; ?></div>
        <textarea class="editor-rico-codigo" data-editor-codigo hidden spellcheck="false"><?php echo htmlspecialchars($valorAtual, ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>
    <input type="hidden" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" data-editor-hidden value="<?php echo htmlspecialchars($valorAtual, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="file" class="editor-rico-arquivo-oculto" data-editor-arquivo accept="image/*" hidden>
</div>
