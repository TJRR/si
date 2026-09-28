<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 52: tela unica de "evento inexistente" (ver
// EventoPublicoController::responderEventoInexistente()). Cabecalho no mesmo
// padrao das demais telas publicas e rodape compartilhado com a pagina do
// evento; nada aqui depende de dado de um evento especifico.
?>
<div class="site-page" id="topo">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($altLogoTexto, ENT_QUOTES, 'UTF-8'); ?>" class="site-logo">
        </div>
    </header>

    <main class="site-form-page">
        <h1>Este evento não existe</h1>
        <p>O endereço acessado não corresponde a nenhum evento disponível. Confira o endereço ou volte à página inicial.</p>
        <p><a href="<?php echo url('home/index'); ?>" class="btn btn-cta">Voltar à página inicial</a></p>
    </main>

    <?php include __DIR__ . '/../home/_rodape.php'; ?>
</div>
