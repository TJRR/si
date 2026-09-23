/**
 * Fase 51: abas por dia da secao "Programacao" na pagina publica do Evento.
 * Marcacao acessivel de verdade: os botoes sao <button> com aria-selected, e
 * cada painel e' um elemento com hidden - sem hidden o CSS nao esconderia
 * nada sozinho, e sem os botoes reais o teclado nao alcancaria as abas.
 */
(function () {
    'use strict';

    var grupos = document.querySelectorAll('[data-programacao]');

    if (!grupos.length) {
        return;
    }

    Array.prototype.forEach.call(grupos, function (grupo) {
        var botoes = grupo.querySelectorAll('[data-programacao-aba]');
        var paineis = grupo.querySelectorAll('[data-programacao-painel]');

        function mostrar(chave) {
            Array.prototype.forEach.call(botoes, function (botao) {
                var ativo = botao.getAttribute('data-programacao-aba') === chave;
                botao.setAttribute('aria-selected', ativo ? 'true' : 'false');
                botao.classList.toggle('ativo', ativo);
            });

            Array.prototype.forEach.call(paineis, function (painel) {
                var ativo = painel.getAttribute('data-programacao-painel') === chave;

                if (ativo) {
                    painel.removeAttribute('hidden');
                } else {
                    painel.setAttribute('hidden', 'hidden');
                }
            });
        }

        Array.prototype.forEach.call(botoes, function (botao) {
            botao.addEventListener('click', function () {
                mostrar(botao.getAttribute('data-programacao-aba'));
            });
        });

        if (botoes.length) {
            mostrar(botoes[0].getAttribute('data-programacao-aba'));
        }
    });
})();
