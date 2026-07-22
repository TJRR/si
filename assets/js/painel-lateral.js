/**
 * Fase 19 (#86): painel lateral generico (deslizante da direita) - hoje so'
 * usado pelo painel do cronograma (#painel-cronograma), mas o nome/logica
 * nao amarram nele: qualquer elemento com `.site-painel-lateral` + o
 * respectivo backdrop `.site-painel-backdrop` funciona com os mesmos
 * gatilhos (`data-abrir-painel-cronograma`, `data-fechar-painel`).
 * Delegacao em `document`, mesmo padrao dos demais componentes desta fase.
 */
(function () {
    'use strict';

    function abrir() {
        var painel = document.getElementById('painel-cronograma');
        var backdrop = document.querySelector('.site-painel-backdrop');

        if (!painel) { return; }

        painel.classList.add('aberto');
        painel.setAttribute('aria-hidden', 'false');
        if (backdrop) { backdrop.classList.add('aberto'); }
    }

    function fechar() {
        document.querySelectorAll('.site-painel-lateral.aberto').forEach(function (painel) {
            painel.classList.remove('aberto');
            painel.setAttribute('aria-hidden', 'true');
        });
        document.querySelectorAll('.site-painel-backdrop.aberto').forEach(function (backdrop) {
            backdrop.classList.remove('aberto');
        });
    }

    document.addEventListener('click', function (evento) {
        if (evento.target.closest && evento.target.closest('[data-abrir-painel-cronograma]')) {
            abrir();
            return;
        }

        if (evento.target.closest && evento.target.closest('[data-fechar-painel]')) {
            fechar();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') { fechar(); }
    });
})();
