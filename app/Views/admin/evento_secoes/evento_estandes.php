<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Mostra na página do evento os estandes ativos, na ordem cadastrada no nó Estandes da árvore (logotipo, categoria, nome e descrição). O código de visita nunca aparece aqui: ele só está no cartaz impresso. Os estandes em si são cadastrados e editados em Estandes; aqui ficam só o título, o texto de apoio e as cores da seção.</p>

<p><a href="<?php echo url('estandes/index/' . (int) $evento['id']); ?>" class="btn-acao">Ir para Estandes</a></p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/estandes/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>

    <button type="submit">Salvar seção</button>
</form>
