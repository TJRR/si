/**
 * Fase 19 (#84): cabecalho publico com imagem de fundo propria - so' entra
 * em acao quando o admin configurou uma imagem (`.site-header-com-imagem`
 * presente). IntersectionObserver observando a sentinela logo apos o
 * cabecalho: quando ela sai da viewport (rolou alem da secao com imagem), o
 * cabecalho vira solido/fixo; quando reentra, volta a flutuar transparente
 * sobre a imagem.
 */
(function () {
    'use strict';

    var cabecalho = document.querySelector('.site-header-com-imagem');
    var nav = document.getElementById('site-header-nav');
    var sentinela = document.querySelector('.site-header-sentinela');

    if (!cabecalho || !nav || !sentinela) {
        return;
    }

    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            nav.classList.toggle('site-header-nav-fixa', !entrada.isIntersecting);
        });
    }, { threshold: 0 });

    observador.observe(sentinela);
})();
