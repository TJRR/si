/**
 * Fase 19 (#84 v4): corrige e dessincroniza o efeito de "boiar" das
 * imagens dentro do titulo rico do cabecalho.
 *
 * Duas coisas resolvidas aqui, nao no CSS:
 * 1. Imagens coladas do site modelo trazem inline `style="animation:
 *    ...bounceHero"` - um nome de animacao que so' existe la', entao
 *    sem correcao nem a nossa nem a delas roda. Sobrescrever apenas
 *    `animationName` (nao o shorthand inteiro) preserva o resto do que
 *    veio na inline (timing-function, delay) e ainda funciona pra
 *    imagens inseridas pelo botao do proprio editor (que nao tem
 *    nenhuma animacao inline pra conflitar).
 * 2. `nth-of-type` no CSS nao serve aqui: cada imagem colada vem dentro
 *    do seu proprio <span> individual, entao toda imagem e' sempre a
 *    unica do tipo dentro do seu pai (sempre bate so' com nth-of-type(1)).
 *    Setando a duracao por indice aqui, na ordem real em que elas
 *    aparecem no documento, e' o unico jeito confiavel de dessincronizar,
 *    reproduzindo o "atraso aleatorio" do site modelo.
 */
(function () {
    'use strict';

    var duracoes = [5, 6, 7, 8, 4.5, 6.5, 7.5];
    var imagens = document.querySelectorAll('.site-header-hero-texto img');

    imagens.forEach(function (imagem, indice) {
        imagem.style.animationName = 'site-header-bounce';
        imagem.style.animationDuration = duracoes[indice % duracoes.length] + 's';
    });
})();
