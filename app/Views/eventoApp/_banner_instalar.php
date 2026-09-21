<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 41 (correcao pos-teste de fumaca): convite de instalacao em
 * destaque, logo abaixo da app-bar - substitui o item de menu escondido
 * (achado do teste de fumaca: ficava discreto demais, quase ninguem ia
 * notar). Escondido por padrao (`hidden`), revelado via JS
 * (assets/js/instalar-app.js) so' quando o navegador dispara o evento
 * nativo `beforeinstallprompt` - nunca antes disso, e nunca de forma
 * forcada (essa decisao continua sendo do navegador, fora do controle da
 * aplicacao). Some definitivamente quando o app e' instalado (evento
 * `appinstalled`).
 */
?>
<button type="button" id="app-banner-instalar" class="app-banner-instalar" hidden>
    <span>📲 Toque para instalar o aplicativo</span>
</button>
