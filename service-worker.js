/**
 * Service worker do aplicativo instalavel do Evento. Fica na raiz do
 * projeto para o escopo de registro cobrir todas as rotas, que passam
 * todas por index.php?r=modulo/acao.
 *
 * O criterio de cache e' `request.mode`, nunca a presenca de "r=" na URL
 * (a pagina inicial publica tambem nao tem "r="): navegacao de pagina
 * nunca e' interceptada; so' sub-recursos dentro de /assets/ saem do
 * cache. Ver Implantar.md, secao 13.4.
 *
 * CACHE_NAME e' versionado a mao: trocar o sufixo quando a lista de
 * arquivos relevantes mudar, para o `activate` limpar o cache antigo.
 */

const CACHE_NAME = 'evento-app-v2';

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (nomes) {
            return Promise.all(
                nomes
                    .filter(function (nome) {
                        return nome !== CACHE_NAME;
                    })
                    .map(function (nome) {
                        return caches.delete(nome);
                    })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    const requisicao = event.request;

    if (requisicao.mode === 'navigate') {
        return;
    }

    const url = new URL(requisicao.url);

    if (url.pathname.indexOf('/assets/') === -1) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.match(requisicao).then(function (respostaCache) {
                const buscaRede = fetch(requisicao)
                    .then(function (respostaRede) {
                        if (respostaRede && respostaRede.ok) {
                            cache.put(requisicao, respostaRede.clone());
                        }
                        return respostaRede;
                    })
                    .catch(function () {
                        return respostaCache;
                    });

                return respostaCache || buscaRede;
            });
        })
    );
});
