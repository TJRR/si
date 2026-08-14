/**
 * Fase 30 (correcao pos-teste): dicionario de palavras-chave e' proprio da
 * tela de Modelos de Documento - fica fora do editor de texto rico
 * compartilhado (assets/js/editor-rico.js), que outras 5 telas tambem usam
 * (Blocos/Slides/Banners/Contatos/Cabecalho). Passar mouse sobre o icone
 * mostra o painel (":hover" em site.css, sem JS); clicar fixa o painel
 * aberto ate' um novo clique no icone ou fora da area.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (evento) {
        var area = document.querySelector('.modelo-dicionario-area');
        var painel = area ? area.querySelector('.modelo-dicionario-painel') : null;

        if (!painel) { return; }

        if (evento.target.closest('.modelo-dicionario-botao')) {
            painel.classList.toggle('aberto');
            return;
        }

        if (!evento.target.closest('.modelo-dicionario-area')) {
            painel.classList.remove('aberto');
        }
    });
})();
