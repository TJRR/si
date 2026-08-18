<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 31: painel de ajuda contextual da Home publica - mesma estrutura de
// _painel_cronograma.php (painel lateral deslizante generico), so' que o
// conteudo ja vem pronto em $ajudaHtml (resolvido por View::renderizar() a
// partir de app/Ajuda/home/index.php).
?>
<aside id="painel-ajuda" class="site-painel-lateral" aria-hidden="true" aria-label="Ajuda desta página">
    <div class="site-painel-cabecalho">
        <h2>Ajuda — <?php echo htmlspecialchars((string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?></h2>
        <button type="button" class="site-painel-fechar" data-fechar-painel aria-label="Fechar">×</button>
    </div>
    <div class="site-painel-corpo">
        <?php echo $ajudaHtml; ?>
    </div>
</aside>
