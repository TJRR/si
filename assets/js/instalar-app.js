/**
 * Fase 41 (correcao pos-teste de fumaca): captura o evento nativo
 * `beforeinstallprompt` do navegador (disparado quando ele proprio decide
 * que o app satisfaz os criterios de instalabilidade - manifest valido,
 * service worker registrado, contexto seguro) para revelar um banner em
 * destaque "Toque para instalar o aplicativo" logo abaixo da app-bar
 * (app/Views/eventoApp/_banner_instalar.php), em vez de depender do
 * usuario encontrar essa opcao escondida no menu de tres pontos do
 * navegador (um item de menu equivalente foi testado antes e achado
 * discreto demais). NAO forca nem antecipa a instalabilidade em si - isso
 * continua sendo decisao do navegador (inclusive heuristicas de
 * engajamento fora do controle da aplicacao); so' evita que a pessoa
 * precise procurar a opcao manualmente quando ela ja estiver disponivel.
 */
(function () {
    'use strict';

    var promptDiferido = null;
    var banner = document.getElementById('app-banner-instalar');

    if (!banner) { return; }

    window.addEventListener('beforeinstallprompt', function (evento) {
        evento.preventDefault();
        promptDiferido = evento;
        banner.hidden = false;
    });

    banner.addEventListener('click', function () {
        if (!promptDiferido) { return; }

        promptDiferido.prompt();
        promptDiferido.userChoice.finally(function () {
            promptDiferido = null;
            banner.hidden = true;
        });
    });

    window.addEventListener('appinstalled', function () {
        banner.hidden = true;
    });
})();
