<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Endereço do evento, texto de apoio e mapa. O mapa incorporado só aceita endereços do Google Maps ou do OpenStreetMap, e é a única parte do sistema que carrega conteúdo de fora: sem endereço de mapa, a seção mostra apenas a imagem enviada.</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/local/' . (int) $secao['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>

    <label>Endereço:
        <input type="text" name="endereco" maxlength="255" value="<?php echo htmlspecialchars((string) $secao['endereco'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <label>Endereço de incorporação do mapa (Google Maps ou OpenStreetMap):
        <input type="url" name="mapa_embed_url" maxlength="500" value="<?php echo htmlspecialchars((string) $secao['mapa_embed_url'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <label>Endereço do botão "Abrir no mapa" (opcional):
        <input type="url" name="mapa_link" maxlength="500" value="<?php echo htmlspecialchars((string) $secao['mapa_link'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <fieldset>
        <legend>Imagem do local (opcional)</legend>
        <?php if (!empty($secao['imagem_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $secao['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:260px;display:block;margin:.5rem 0;">
        <?php endif; ?>
        <label>Imagem: <input type="file" name="imagem" accept="image/*"></label>
        <label>Texto alternativo da imagem:
            <input type="text" name="imagem_alt" maxlength="255" value="<?php echo htmlspecialchars((string) $secao['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <button type="submit">Salvar seção</button>
</form>
