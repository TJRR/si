/**
 * Reabertura da Fase 51: comportamentos proprios da pagina publica do
 * Evento, sem nenhuma biblioteca externa.
 *
 * 1. Distancia de rolagem das ancoras: o cabecalho e' fixo no topo, entao
 *    cada secao precisa parar abaixo dele, e nao atras dele (o titulo da
 *    secao sumia ao clicar num item do menu). A altura real do cabecalho e'
 *    medida e gravada na variavel CSS --evento-altura-cabecalho, usada pelo
 *    scroll-margin-top das secoes; e' medida de novo quando a janela muda
 *    de tamanho, porque no celular o cabecalho tem outra altura.
 * 2. Entrada animada: elementos marcados com data-evento-entrada aparecem
 *    em sequencia quando entram na tela. Sem IntersectionObserver, ou com
 *    "reduzir movimento" ligado no sistema, tudo aparece de uma vez.
 */
(function () {
    'use strict';

    var pagina = document.querySelector('.evento-pagina');

    if (!pagina) {
        return;
    }

    var cabecalho = document.getElementById('site-header-nav') || document.querySelector('.site-header');

    function medirCabecalho() {
        if (!cabecalho) {
            return;
        }

        pagina.style.setProperty('--evento-altura-cabecalho', (cabecalho.offsetHeight + 12) + 'px');
    }

    medirCabecalho();
    window.addEventListener('resize', medirCabecalho);

    var elementos = Array.prototype.slice.call(pagina.querySelectorAll('[data-evento-entrada]'));
    var reduzirMovimento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!elementos.length) {
        return;
    }

    if (reduzirMovimento || !('IntersectionObserver' in window)) {
        elementos.forEach(function (elemento) {
            elemento.classList.add('evento-entrou');
        });
        return;
    }

    pagina.classList.add('evento-entrada-ativa');

    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (!entrada.isIntersecting) {
                return;
            }

            entrada.target.classList.add('evento-entrou');
            observador.unobserve(entrada.target);
        });
    }, { threshold: 0.15 });

    elementos.forEach(function (elemento) {
        observador.observe(elemento);
    });
})();
