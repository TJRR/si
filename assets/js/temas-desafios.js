/**
 * Fase 19 (#103): secao "Desafios" da home publica - abas por trilha,
 * cards de Tema recolhiveis e busca por palavra-chave. JS vanilla puro,
 * sem lib (mesma decisao de todo o projeto), delegacao de evento em
 * `document` (mesmo padrao de reordenar-arrastar.js/editor-rico.js).
 */
(function () {
    'use strict';

    var secao = document.getElementById('temas');

    if (!secao) {
        return;
    }

    function normalizar(texto) {
        return (texto || '')
            .toString()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase();
    }

    function expandirCard(card, expandir) {
        card.classList.toggle('expandido', expandir);
        var cabecalho = card.querySelector(':scope > .tema-card-cabecalho');
        if (cabecalho) {
            cabecalho.setAttribute('aria-expanded', expandir ? 'true' : 'false');
        }
    }

    function restaurarPadrao(conteudo) {
        var cards = conteudo.querySelectorAll(':scope > .tema-card');
        cards.forEach(function (card) {
            card.style.display = '';
            expandirCard(card, false);
            card.querySelectorAll('.tema-desafio-item').forEach(function (item) {
                item.style.display = '';
            });
        });
    }

    function aplicarBusca(conteudo, termoBruto) {
        var termo = normalizar(termoBruto);

        if (termo === '') {
            restaurarPadrao(conteudo);
            return;
        }

        var cards = conteudo.querySelectorAll(':scope > .tema-card');

        cards.forEach(function (card) {
            var itens = card.querySelectorAll('.tema-desafio-item');
            var algumVisivel = false;

            itens.forEach(function (item) {
                var texto = normalizar(item.dataset.temaDesafioTexto);
                var bate = texto.indexOf(termo) !== -1;
                item.style.display = bate ? '' : 'none';
                if (bate) { algumVisivel = true; }
            });

            card.style.display = algumVisivel ? '' : 'none';
            if (algumVisivel) {
                expandirCard(card, true);
            }
        });
    }

    var campoBusca = secao.querySelector('[data-temas-busca]');

    // Abas
    secao.addEventListener('click', function (evento) {
        var botaoAba = evento.target.closest && evento.target.closest('[data-temas-aba]');
        if (botaoAba) {
            var indice = botaoAba.dataset.temasAba;

            secao.querySelectorAll('.temas-aba').forEach(function (aba) {
                var ativa = aba === botaoAba;
                aba.classList.toggle('ativo', ativa);
                aba.setAttribute('aria-selected', ativa ? 'true' : 'false');
            });

            secao.querySelectorAll('.temas-eixo-conteudo').forEach(function (conteudo) {
                conteudo.classList.toggle('ativo', conteudo.dataset.temasConteudo === indice);
            });

            // A mesma palavra-chave (se houver) continua filtrando na aba nova.
            var conteudoAtivo = secao.querySelector('.temas-eixo-conteudo.ativo');
            if (conteudoAtivo && campoBusca) {
                aplicarBusca(conteudoAtivo, campoBusca.value);
            }

            return;
        }

        var botaoToggle = evento.target.closest && evento.target.closest('[data-tema-toggle]');
        if (botaoToggle) {
            var card = botaoToggle.closest('.tema-card');
            if (card) {
                expandirCard(card, !card.classList.contains('expandido'));
            }
        }
    });

    // Busca (so' filtra dentro da aba ativa no momento da digitacao)
    if (campoBusca) {
        campoBusca.addEventListener('input', function () {
            var conteudoAtivo = secao.querySelector('.temas-eixo-conteudo.ativo');
            if (conteudoAtivo) {
                aplicarBusca(conteudoAtivo, campoBusca.value);
            }
        });
    }
})();
