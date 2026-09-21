/**
 * Fase 42 (correcao pos-teste de fumaca): botao "Aumentar brilho para
 * leitura" do cartao de credenciamento (eventoApp/inscricao.php) - nao
 * existe API padrao de navegador pra controlar o brilho real da tela, entao
 * o efeito e' abrir um overlay em tela cheia com fundo branco puro e o
 * mesmo QR ampliado: a propria tela emite mais luz sobre uma area maior e
 * mais clara, o que ajuda na leitura em ambientes com pouca luz - sem
 * prometer o que o navegador nao pode entregar. Toca em qualquer ponto do
 * overlay (ou no proprio botao de novo) fecha.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (evento) {
        var gatilho = evento.target.closest && evento.target.closest('[data-toggle-brilho]');

        if (gatilho) {
            var overlay = document.getElementById(gatilho.dataset.toggleBrilho);
            if (overlay) { overlay.hidden = !overlay.hidden; }
            return;
        }

        var overlayAberto = evento.target.closest && evento.target.closest('[data-fechar-overlay-brilho]');
        if (overlayAberto) { overlayAberto.hidden = true; }
    });
})();
