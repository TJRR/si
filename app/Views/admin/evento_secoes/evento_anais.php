<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Mostra na página do evento o botão para baixar a versão publicada dos Anais e, se você quiser, a relação dos trabalhos selecionados. O volume é publicado em Trabalhos, Anais; sem volume publicado, a seção mostra só o título e o texto de apoio. A relação dos selecionados só aparece depois de o resultado de Trabalhos ser publicado, traz título, autoria e eixo, e nunca mostra nota nem posição.</p>

<p><a href="<?php echo url('trabalhoAnais/index/' . (int) $evento['id']); ?>" class="btn-acao">Ir para os Anais</a></p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/anais/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>

    <label>
        <input type="checkbox" name="mostrar_selecionados" <?php echo !empty($secao['mostrar_selecionados']) ? 'checked' : ''; ?>>
        Mostrar a relação dos trabalhos selecionados (só depois de o resultado de Trabalhos ser publicado)
    </label>

    <button type="submit">Salvar seção</button>
</form>
