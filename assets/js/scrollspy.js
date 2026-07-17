/**
 * Fase 18 (Navegabilidade, secao 5): destaque automatico do item de menu
 * correspondente a secao visivel (IntersectionObserver, sem lib) + toggle
 * do menu mobile do cabecalho publico.
 */
(function () {
    'use strict';

    var links = Array.prototype.slice.call(document.querySelectorAll('[data-scrollspy-alvo]'));

    if (links.length > 0 && 'IntersectionObserver' in window) {
        var linksPorAncora = {};

        links.forEach(function (link) {
            linksPorAncora[link.dataset.scrollspyAlvo] = link;
        });

        var observador = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                var link = linksPorAncora[entrada.target.id];

                if (!link) {
                    return;
                }

                if (entrada.isIntersecting) {
                    links.forEach(function (outro) { outro.classList.remove('ativo'); });
                    link.classList.add('ativo');
                }
            });
        }, { rootMargin: '-40% 0px -55% 0px' });

        Object.keys(linksPorAncora).forEach(function (ancora) {
            var alvo = document.getElementById(ancora);
            if (alvo) { observador.observe(alvo); }
        });
    }

    var botaoMenu = document.querySelector('.site-menu-toggle');
    var navPrincipal = document.getElementById('site-nav-principal');

    if (botaoMenu && navPrincipal) {
        botaoMenu.addEventListener('click', function () {
            var aberto = navPrincipal.classList.toggle('aberto');
            botaoMenu.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        });

        navPrincipal.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navPrincipal.classList.remove('aberto');
                botaoMenu.setAttribute('aria-expanded', 'false');
            });
        });
    }
})();
