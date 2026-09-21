/**
 * Fase 19 (#86): painel lateral generico (deslizante da direita) - hoje
 * usado pelo painel do cronograma (#painel-cronograma) e, desde a Fase 31,
 * pelo painel de ajuda contextual da Home publica (#painel-ajuda): qualquer
 * elemento com `.site-painel-lateral` + o backdrop compartilhado
 * `.site-painel-backdrop` funciona com os mesmos gatilhos - o alvo e'
 * escolhido pelo atributo `data-abrir-painel="<id-do-painel>"` no elemento
 * clicado (`data-fechar-painel` fecha qualquer painel aberto).
 * Delegacao em `document`, mesmo padrao dos demais componentes desta fase.
 *
 * Fase 41: generalizado para reconhecer tambem `.app-menu-lateral`/
 * `.app-menu-backdrop` (menu dropdown do aplicativo do Evento, ancorado no
 * proprio botao que o abre - ver app/Views/eventoApp/_menu_painel.php) -
 * mesmo mecanismo de abrir/fechar por id, aparencia visual propria e
 * deliberadamente diferente (definida em assets/css/site.css), sem duplicar
 * este script. Tambem passou a alternar (toggle): clicar de novo no mesmo
 * gatilho [data-abrir-painel] fecha o painel se ja estiver aberto, em vez
 * de so' abrir - necessario para o botao hamburguer da app-bar funcionar
 * como abre/fecha sem precisar de um botao "fechar" separado dentro do
 * proprio menu.
 */
(function () {
    'use strict';

    var SELETOR_PAINEL = '.site-painel-lateral, .app-menu-lateral';
    var SELETOR_BACKDROP = '.site-painel-backdrop, .app-menu-backdrop';

    function abrir(idPainel) {
        var painel = document.getElementById(idPainel);
        var backdrop = document.querySelector(SELETOR_BACKDROP);

        if (!painel) { return; }

        if (painel.classList.contains('aberto')) {
            fechar();
            return;
        }

        painel.classList.add('aberto');
        painel.setAttribute('aria-hidden', 'false');
        if (backdrop) { backdrop.classList.add('aberto'); }
    }

    function fechar() {
        document.querySelectorAll(SELETOR_PAINEL).forEach(function (painel) {
            painel.classList.remove('aberto');
            painel.setAttribute('aria-hidden', 'true');
        });
        document.querySelectorAll(SELETOR_BACKDROP).forEach(function (backdrop) {
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
