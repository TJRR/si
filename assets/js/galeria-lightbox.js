/**
 * Lightbox de galeria de imagens, 100% vanilla (sem biblioteca de terceiros,
 * sem build step) - mesma UX do carrossel de fotos do projeto LG Conecta
 * (setas anterior/proxima, legenda, fechar por X/ESC/clique fora, foco
 * devolvido ao elemento que abriu), implementacao propria em JS puro pra
 * manter a mesma stack do resto do SI.
 *
 * Nao reaproveita o #modal-generico (assets/js/modal.js): aquele busca HTML
 * remoto via fetch e navega por linhas de uma <table>; aqui os dados sao
 * locais (atributos data-* dos proprios itens da grade), sem ida ao
 * servidor - fonte de dados e finalidade diferentes o suficiente pra nao
 * valer a pena forcar o mesmo componente.
 *
 * Delegacao de evento em `document` (nunca listener direto no elemento),
 * mesma convencao de editor-rico.js.
 */
(function () {
    'use strict';

    var itens = [];
    var indiceAtual = -1;
    var elementoQueAbriu = null;

    function overlay() {
        return document.getElementById('lightbox-galeria');
    }

    function coletarItens() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-lightbox-src]'));
    }

    function mostrarIndice(indice) {
        if (itens.length === 0) {
            return;
        }

        indiceAtual = (indice + itens.length) % itens.length;

        var item = itens[indiceAtual];
        var imagem = document.getElementById('lightbox-imagem');
        var legenda = document.getElementById('lightbox-legenda');

        imagem.src = item.getAttribute('data-lightbox-src');
        imagem.alt = item.getAttribute('data-lightbox-alt') || '';
        legenda.textContent = item.getAttribute('data-lightbox-legenda') || '';

        var multiplos = itens.length > 1;
        document.querySelectorAll('.lightbox-seta').forEach(function (seta) {
            seta.hidden = !multiplos;
        });
    }

    function abrir(indice, origem) {
        itens = coletarItens();

        if (itens.length === 0) {
            return;
        }

        elementoQueAbriu = origem || null;
        mostrarIndice(indice);

        var caixa = overlay();
        caixa.hidden = false;
        document.body.style.overflow = 'hidden';

        var botaoFechar = caixa.querySelector('.lightbox-fechar');
        if (botaoFechar) {
            botaoFechar.focus();
        }
    }

    function fechar() {
        var caixa = overlay();

        if (caixa === null || caixa.hidden) {
            return;
        }

        caixa.hidden = true;
        document.body.style.overflow = '';

        if (elementoQueAbriu) {
            elementoQueAbriu.focus();
            elementoQueAbriu = null;
        }
    }

    function proxima() {
        mostrarIndice(indiceAtual + 1);
    }

    function anterior() {
        mostrarIndice(indiceAtual - 1);
    }

    document.addEventListener('click', function (evento) {
        var itemGaleria = evento.target.closest('[data-lightbox-src]');
        if (itemGaleria) {
            var todos = coletarItens();
            abrir(todos.indexOf(itemGaleria), itemGaleria);
            return;
        }

        if (evento.target.closest('.lightbox-fechar')) {
            fechar();
            return;
        }

        if (evento.target.closest('.lightbox-proxima')) {
            proxima();
            return;
        }

        if (evento.target.closest('.lightbox-anterior')) {
            anterior();
            return;
        }

        // Clique no fundo escuro (fora da figura) fecha - mesmo padrão do
        // LG Conecta (@click.self).
        if (evento.target === overlay()) {
            fechar();
        }
    });

    document.addEventListener('keydown', function (evento) {
        var caixa = overlay();
        if (caixa === null || caixa.hidden) {
            return;
        }

        if (evento.key === 'Escape') {
            fechar();
        } else if (evento.key === 'ArrowRight') {
            proxima();
        } else if (evento.key === 'ArrowLeft') {
            anterior();
        }
    });
})();
