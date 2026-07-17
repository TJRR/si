/**
 * Fase 18: modulo generico de reordenacao por arrastar-e-soltar (HTML5 DnD
 * nativo, sem lib - nao havia nenhum precedente de drag-and-drop no projeto
 * ate esta fase). Botoes "mover acima/abaixo" sao a alternativa por teclado,
 * sempre visiveis (nao pode depender so' de mouse - requisito de
 * acessibilidade da propria fase).
 *
 * Marcacao esperada em qualquer listagem admin que precise reordenar:
 *   <ul class="reordenar-lista" data-reordenar-rota="slides/reordenar">
 *     <li class="reordenar-item" draggable="true" data-id="12">
 *       <span class="reordenar-alca" aria-hidden="true">⠿</span>
 *       <div class="reordenar-conteudo">...</div>
 *       <div class="reordenar-botoes">
 *         <button type="button" data-mover="cima" aria-label="Mover para cima">▲</button>
 *         <button type="button" data-mover="baixo" aria-label="Mover para baixo">▼</button>
 *       </div>
 *     </li>
 *     ...
 *   </ul>
 *
 * 100% delegacao de evento em `document` - mesma razao de editor-rico.js
 * (telas carregadas via arvore/modal, innerHTML nao preserva listeners
 * presos a um elemento especifico).
 */
(function () {
    'use strict';

    var itemArrastando = null;

    function url(rota) {
        return window.SI_BASE_PATH + '/index.php?r=' + rota;
    }

    function atualizarEstadoBotoes(lista) {
        var itens = lista.querySelectorAll(':scope > .reordenar-item');

        itens.forEach(function (item, indice) {
            var botaoCima = item.querySelector('[data-mover="cima"]');
            var botaoBaixo = item.querySelector('[data-mover="baixo"]');

            if (botaoCima) { botaoCima.disabled = indice === 0; }
            if (botaoBaixo) { botaoBaixo.disabled = indice === itens.length - 1; }
        });
    }

    function enviarNovaOrdem(lista) {
        var ids = Array.prototype.map.call(
            lista.querySelectorAll(':scope > .reordenar-item'),
            function (item) { return item.dataset.id; }
        );

        fetch(url(lista.dataset.reordenarRota), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ ids: ids })
        })
            .then(function (resposta) {
                if (!resposta.ok) { throw new Error('falha'); }
            })
            .catch(function () {
                window.alert('Não foi possível salvar a nova ordem. A página será recarregada.');
                location.reload();
            });
    }

    document.addEventListener('dragstart', function (evento) {
        var item = evento.target.closest && evento.target.closest('.reordenar-item');
        if (!item) { return; }

        itemArrastando = item;
        item.classList.add('arrastando');
        evento.dataTransfer.effectAllowed = 'move';
        evento.dataTransfer.setData('text/plain', item.dataset.id);
    });

    document.addEventListener('dragover', function (evento) {
        if (!itemArrastando) { return; }

        var alvo = evento.target.closest && evento.target.closest('.reordenar-item');
        if (!alvo || alvo === itemArrastando || alvo.parentElement !== itemArrastando.parentElement) { return; }

        evento.preventDefault();

        var retangulo = alvo.getBoundingClientRect();
        var depois = (evento.clientY - retangulo.top) > (retangulo.height / 2);

        alvo.parentElement.insertBefore(itemArrastando, depois ? alvo.nextSibling : alvo);
    });

    document.addEventListener('dragend', function () {
        if (!itemArrastando) { return; }

        var lista = itemArrastando.closest('.reordenar-lista');
        itemArrastando.classList.remove('arrastando');
        itemArrastando = null;

        if (lista) {
            atualizarEstadoBotoes(lista);
            enviarNovaOrdem(lista);
        }
    });

    document.addEventListener('click', function (evento) {
        var botao = evento.target.closest && evento.target.closest('[data-mover]');
        if (!botao) { return; }

        var item = botao.closest('.reordenar-item');
        var lista = item ? item.closest('.reordenar-lista') : null;
        if (!item || !lista) { return; }

        var vizinho = botao.dataset.mover === 'cima' ? item.previousElementSibling : item.nextElementSibling;
        if (!vizinho) { return; }

        if (botao.dataset.mover === 'cima') {
            lista.insertBefore(item, vizinho);
        } else {
            lista.insertBefore(vizinho, item);
        }

        atualizarEstadoBotoes(lista);
        enviarNovaOrdem(lista);
        botao.focus();
    });

    document.querySelectorAll('.reordenar-lista').forEach(atualizarEstadoBotoes);
})();
