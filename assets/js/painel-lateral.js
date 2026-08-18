/**
 * Fase 19 (#86): painel lateral generico (deslizante da direita) - hoje
 * usado pelo painel do cronograma (#painel-cronograma) e, desde a Fase 31,
 * pelo painel de ajuda contextual da Home publica (#painel-ajuda): qualquer
 * elemento com `.site-painel-lateral` + o backdrop compartilhado
 * `.site-painel-backdrop` funciona com os mesmos gatilhos - o alvo e'
 * escolhido pelo atributo `data-abrir-painel="<id-do-painel>"` no elemento
 * clicado (`data-fechar-painel` fecha qualquer painel aberto).
 * Delegacao em `document`, mesmo padrao dos demais componentes desta fase.
 */
(function () {
    'use strict';

    function abrir(idPainel) {
        var painel = document.getElementById(idPainel);
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
        var gatilho = evento.target.closest && evento.target.closest('[data-abrir-painel]');

        if (gatilho) {
            abrir(gatilho.dataset.abrirPainel);
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
