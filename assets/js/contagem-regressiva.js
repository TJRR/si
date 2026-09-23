/**
 * Fase 51: contagem regressiva da pagina publica do Evento. Le a data alvo
 * do proprio elemento (data-alvo, no formato ISO) e atualiza dias, horas,
 * minutos e segundos a cada segundo. Sem biblioteca externa, como todo o
 * JavaScript deste projeto.
 *
 * Quando a data ja passou, mostra zeros e para o relogio: a secao continua
 * na pagina (quem configurou decide quando tirar), so nao fica contando
 * tempo negativo.
 */
(function () {
    'use strict';

    var contadores = document.querySelectorAll('[data-contagem-alvo]');

    if (!contadores.length) {
        return;
    }

    function doisDigitos(valor) {
        return valor < 10 ? '0' + valor : String(valor);
    }

    function atualizar(contador) {
        var alvo = new Date(contador.getAttribute('data-contagem-alvo').replace(' ', 'T'));
        var restante = Math.floor((alvo.getTime() - Date.now()) / 1000);

        if (isNaN(restante)) {
            return false;
        }

        if (restante < 0) {
            restante = 0;
        }

        var dias = Math.floor(restante / 86400);
        var horas = Math.floor((restante % 86400) / 3600);
        var minutos = Math.floor((restante % 3600) / 60);
        var segundos = restante % 60;

        var campoDias = contador.querySelector('[data-contagem-dias]');
        var campoHora = contador.querySelector('[data-contagem-hora]');

        if (campoDias) {
            campoDias.textContent = String(dias);
        }

        if (campoHora) {
            campoHora.textContent = doisDigitos(horas) + 'h ' + doisDigitos(minutos) + 'm ' + doisDigitos(segundos) + 's';
        }

        return restante > 0;
    }

    Array.prototype.forEach.call(contadores, function (contador) {
        if (!atualizar(contador)) {
            return;
        }

        var temporizador = window.setInterval(function () {
            if (!atualizar(contador)) {
                window.clearInterval(temporizador);
            }
        }, 1000);
    });
})();
