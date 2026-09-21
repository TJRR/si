<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 48B (correcao pos-teste de fumaca): botao de ajuda contextual do
 * app - mesmo padrao de posicionamento da tela de login (.guest-ajuda-botao,
 * ancorado no canto do cartao de conteudo, nao da tela inteira), com
 * aparencia adaptada ao app (pequeno, fundo translucido, em vez de branco
 * solido) - ja tentou 2 outras posicoes antes (dentro do cabecalho, como
 * item do menu suspenso) e um icone fixo na tela que colidiu com o botao
 * de WhatsApp (.site-whatsapp-flutuante, mesmo canto). Incluido dentro de
 * .site-form-page (position:relative) por cada uma das 8 views do app,
 * logo apos abri-la.
 */
?>
<?php if (isset($ajudaHtml) && $ajudaHtml !== null): ?>
    <button type="button" class="app-ajuda-card" title="Ajuda desta página" aria-label="Ajuda desta página" data-ajuda-titulo="<?php echo htmlspecialchars('Ajuda: ' . (string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModal(this.dataset.ajudaTitulo, document.getElementById('ajuda-painel-fonte').innerHTML)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
    </button>
<?php endif; ?>
