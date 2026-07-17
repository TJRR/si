/**
 * Fase 18 (3.2): slideshow da home publica - JS vanilla puro, sem Alpine.js
 * nem nenhuma lib (decisao explicita da fase, manter zero dependencia nova).
 * Auto-avanco pausado no hover/foco e em prefers-reduced-motion; setas e
 * marcadores navegaveis por teclado (sao <button> nativos).
 */
(function () {
    'use strict';

    var secao = document.getElementById('slideshow-principal');

    if (!secao) {
        return;
    }

    var slides = Array.prototype.slice.call(secao.querySelectorAll('.site-slide'));
    var marcadores = Array.prototype.slice.call(secao.querySelectorAll('[data-slideshow-ir]'));
    var indiceAtual = 0;
    var temporizador = null;
    var reduzirMovimento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function irPara(indice) {
        slides[indiceAtual].classList.remove('ativo');
        if (marcadores[indiceAtual]) { marcadores[indiceAtual].classList.remove('ativo'); }

        indiceAtual = (indice + slides.length) % slides.length;

        slides[indiceAtual].classList.add('ativo');
        if (marcadores[indiceAtual]) { marcadores[indiceAtual].classList.add('ativo'); }
    }

    function proximo() { irPara(indiceAtual + 1); }
    function anterior() { irPara(indiceAtual - 1); }

    function iniciarAutoAvanco() {
        if (reduzirMovimento || slides.length < 2) {
            return;
        }

        temporizador = window.setInterval(proximo, 7000);
    }

    function pausarAutoAvanco() {
        if (temporizador) {
            window.clearInterval(temporizador);
            temporizador = null;
        }
    }

    var botaoProximo = secao.querySelector('[data-slideshow-proxima]');
    var botaoAnterior = secao.querySelector('[data-slideshow-anterior]');

    if (botaoProximo) { botaoProximo.addEventListener('click', function () { proximo(); pausarAutoAvanco(); iniciarAutoAvanco(); }); }
    if (botaoAnterior) { botaoAnterior.addEventListener('click', function () { anterior(); pausarAutoAvanco(); iniciarAutoAvanco(); }); }

    marcadores.forEach(function (marcador) {
        marcador.addEventListener('click', function () {
            irPara(parseInt(marcador.dataset.slideshowIr, 10));
            pausarAutoAvanco();
            iniciarAutoAvanco();
        });
    });

    secao.addEventListener('mouseenter', pausarAutoAvanco);
    secao.addEventListener('mouseleave', iniciarAutoAvanco);
    secao.addEventListener('focusin', pausarAutoAvanco);
    secao.addEventListener('focusout', iniciarAutoAvanco);

    iniciarAutoAvanco();
})();
