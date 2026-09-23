<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Campos que toda seção da página do evento tem: etiqueta (o texto pequeno
 * acima do título), título, texto de apoio e as duas cores. Incluído por
 * cada formulário de componente, para o cadastro ficar igual em todos.
 * Espera $secao.
 */
?>
<label>Etiqueta (texto pequeno acima do título):
    <input type="text" name="etiqueta" maxlength="60" value="<?php echo htmlspecialchars((string) $secao['etiqueta'], ENT_QUOTES, 'UTF-8'); ?>">
</label>

<label>Título da seção:
    <input type="text" name="titulo" maxlength="150" value="<?php echo htmlspecialchars((string) $secao['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
</label>

<fieldset>
    <legend>Texto de apoio (opcional)</legend>
    <?php
    $nome = 'descricao_html';
    $valor = (string) $secao['descricao_html'];
    $rotulo = null;
    include __DIR__ . '/../_editor_rico.php';
    ?>
</fieldset>

<label>Cor de fundo da seção:
    <input type="text" name="cor_fundo" maxlength="7" placeholder="#ffffff" value="<?php echo htmlspecialchars((string) $secao['cor_fundo'], ENT_QUOTES, 'UTF-8'); ?>">
</label>

<label>Cor do texto da seção:
    <input type="text" name="cor_texto" maxlength="7" placeholder="#141413" value="<?php echo htmlspecialchars((string) $secao['cor_texto'], ENT_QUOTES, 'UTF-8'); ?>">
</label>
