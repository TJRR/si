/*
 * Fase 41: service worker do aplicativo web instalavel (PWA) do Evento.
 * Arquivo estatico solto na raiz do projeto (mesmo padrao de favicon.ico/
 * termos.php/politica.php) - precisa ficar aqui para o escopo de registro
 * cobrir /si/* em producao (nao da' pra restringir por path, todo o
 * roteamento do sistema e' via query string index.php?r=modulo/acao).
 *
 * Regra de seguranca central (ver plano da Fase 41): o sistema roda tudo por
 * index.php?r=..., entao a HOME PUBLICA tambem nao tem "r=" na URL - uma
 * allowlist que decidisse "sem r= e' asset estatico, pode cachear" cacharia
 * a propria home, que mistura divulgacao do Evento com banners/blocos do
 * Concurso vigente (5o Premio de Inovacao, em avaliacao real). Por isso o
 * criterio AQUI e' `request.mode`, nunca a presenca de "r=" na URL:
 *
 *   1. Navegacao de pagina (qualquer HTML, com ou sem r=) NUNCA e'
 *      interceptada - handler nem chama respondWith(), passa direto pra
 *      rede como se este arquivo nao existisse. Cobre home, admin,
 *      avaliacao, concurso E as proprias telas do evento.
 *   2. So' sub-recursos estaticos dentro de /assets/ (CSS/JS/icones) podem
 *      ser servidos do cache - identificados por PATH, nunca por
 *      querystring. Estrategia "stale-while-revalidate": serve do cache
 *      se ja tiver, atualiza em segundo plano, cai pra rede se nao tiver
 *      nada ainda.
 *   3. eventoApp/manifesto fica de fora de proposito - e' conteudo
 *      dinamico (nome/icone configuraveis pelo Admin em Configuracoes
 *      Gerais), sem cache-buster proprio como os icones (?v=<timestamp>);
 *      cachear traria o mesmo risco de dado desatualizado que esta fase
 *      existe para evitar. Como nao mora em /assets/, a regra 2 ja o
 *      exclui automaticamente.
 *
 * CACHE_NAME e' versionado a mao - trocar o sufixo (v1 -> v2) quando a lista
 * de assets relevantes mudar numa fase futura, para o `activate` limpar o
 * cache antigo (documentado em Implantar.md).
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
