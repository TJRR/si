/**
 * Fase 18: editor de texto rico 100% vanilla (contentEditable + execCommand +
 * Range API), sem biblioteca de terceiros (TipTap/Quill/Trix) e sem build
 * step - decisao explicita para manter o NPI 100% PHP + JS vanilla. UX
 * inspirada no editor do projeto LG Conecta (mesma barra de botoes), mas
 * implementacao propria.
 *
 * 100% delegacao de evento em `document` (nunca listener direto no elemento)
 * porque as telas admin desta fase sao carregadas via arvore/modal
 * (innerHTML), e um <script> ou listener preso a um elemento especifico nao
 * sobrevive a isso (mesma licao da Fase 16 documentada em
 * navegacao-arvore.js). Como o componente nao precisa de nenhuma
 * inicializacao por elemento (contenteditable funciona assim que existe no
 * DOM), nenhum "montar()" e' necessario - só os listeners globais abaixo.
 *
 * Conteudo e' HTML confiavel: só o perfil 'administrador' grava nestes
 * campos (RoleMiddleware::exigir(['administrador']) em cada controller que
 * usa este componente), o mesmo nivel de confianca que esse perfil já tem
 * sobre todo o resto do sistema - por isso o HTML e' salvo e reexibido sem
 * sanitizacao adicional, igual a qualquer outro dado editado por admin.
 */
(function () {
    'use strict';

    var rangesSalvos = new WeakMap();

    function areaDoControle(elemento) {
        var container = elemento.closest('.editor-rico');
        return container ? container.querySelector('[data-editor-area]') : null;
    }

    function salvarSelecao(area) {
        var selecao = window.getSelection();

        if (selecao.rangeCount > 0 && area.contains(selecao.anchorNode)) {
            rangesSalvos.set(area, selecao.getRangeAt(0).cloneRange());
        }
    }

    function restaurarSelecao(area) {
        var range = rangesSalvos.get(area);
        var selecao = window.getSelection();
        selecao.removeAllRanges();

        if (range) {
            selecao.addRange(range);
            return;
        }

        area.focus();
        var novoRange = document.createRange();
        novoRange.selectNodeContents(area);
        novoRange.collapse(false);
        selecao.addRange(novoRange);
    }

    function sincronizarHidden(area) {
        var hidden = area.closest('.editor-rico').querySelector('[data-editor-hidden]');
        hidden.value = area.innerHTML;
    }

    function aplicarEstiloNaSelecao(area, propriedade, valor) {
        restaurarSelecao(area);
        var selecao = window.getSelection();

        if (selecao.rangeCount === 0 || selecao.getRangeAt(0).collapsed) {
            return;
        }

        var range = selecao.getRangeAt(0);
        var span = document.createElement('span');
        span.style[propriedade] = valor;

        try {
            range.surroundContents(span);
        } catch (erro) {
            span.appendChild(range.extractContents());
            range.insertNode(span);
        }

        var novoRange = document.createRange();
        novoRange.selectNodeContents(span);
        selecao.removeAllRanges();
        selecao.addRange(novoRange);
        rangesSalvos.set(area, novoRange.cloneRange());
        sincronizarHidden(area);
    }

    function inserirNaPosicao(area, no) {
        restaurarSelecao(area);
        var selecao = window.getSelection();
        var range = selecao.rangeCount > 0 ? selecao.getRangeAt(0) : null;

        if (!range) {
            area.appendChild(no);
        } else {
            range.deleteContents();
            range.insertNode(no);
            range.setStartAfter(no);
            range.collapse(true);
            selecao.removeAllRanges();
            selecao.addRange(range);
        }

        sincronizarHidden(area);
    }

    var mapaAlinhamento = {
        left: 'justifyLeft',
        center: 'justifyCenter',
        right: 'justifyRight',
        justify: 'justifyFull'
    };

    function executarComando(area, comando, valor) {
        area.focus();
        restaurarSelecao(area);

        switch (comando) {
            case 'negrito': document.execCommand('bold'); break;
            case 'italico': document.execCommand('italic'); break;
            case 'sublinhado': document.execCommand('underline'); break;
            case 'riscado': document.execCommand('strikeThrough'); break;
            case 'subscrito': document.execCommand('subscript'); break;
            case 'sobrescrito': document.execCommand('superscript'); break;
            case 'listaMarcadores': document.execCommand('insertUnorderedList'); break;
            case 'listaNumerada': document.execCommand('insertOrderedList'); break;
            case 'alinhar': document.execCommand(mapaAlinhamento[valor] || 'justifyLeft'); break;
            case 'fonte': if (valor) { aplicarEstiloNaSelecao(area, 'fontFamily', valor); } return;
            case 'tamanho': if (valor) { aplicarEstiloNaSelecao(area, 'fontSize', valor + 'px'); } return;
            case 'cor': aplicarEstiloNaSelecao(area, 'color', valor); return;
            case 'realce': aplicarEstiloNaSelecao(area, 'backgroundColor', valor); return;
            case 'semRealce': document.execCommand('removeFormat'); break;
            case 'link':
                var url = window.prompt('Endereço do link (https://...)');
                if (url) { document.execCommand('createLink', false, url); }
                break;
            case 'barra':
                var divisor = document.createElement('div');
                divisor.setAttribute('data-barra', '');
                divisor.style.cssText = 'height:4px;border-radius:2px;background-color:' + (valor || '#F38123') + ';margin:12px 0;';
                inserirNaPosicao(area, divisor);
                return;
            case 'imagem':
                salvarSelecao(area);
                area.closest('.editor-rico').querySelector('[data-editor-arquivo]').click();
                return;
        }

        sincronizarHidden(area);
    }

    function enviarImagem(area, arquivo) {
        var dados = new FormData();
        dados.append('imagem', arquivo);

        fetch(window.SI_BASE_PATH + '/index.php?r=editorMidia/uploadImagem', {
            method: 'POST',
            body: dados,
            credentials: 'same-origin'
        })
            .then(function (resposta) { return resposta.json(); })
            .then(function (dadosResposta) {
                if (dadosResposta.erro) {
                    window.alert(dadosResposta.erro);
                    return;
                }

                var imagem = document.createElement('img');
                imagem.src = dadosResposta.url;
                imagem.alt = '';
                imagem.style.maxWidth = '100%';
                inserirNaPosicao(area, imagem);
            })
            .catch(function () {
                window.alert('Não foi possível enviar a imagem.');
            });
    }

    document.addEventListener('mouseup', function (evento) {
        var area = evento.target.closest && evento.target.closest('[data-editor-area]');
        if (area) { salvarSelecao(area); }
    });

    document.addEventListener('keyup', function (evento) {
        var area = evento.target.closest && evento.target.closest('[data-editor-area]');
        if (area) { salvarSelecao(area); }
    });

    document.addEventListener('input', function (evento) {
        var area = evento.target.closest && evento.target.closest('[data-editor-area]');
        if (area) { sincronizarHidden(area); }
    });

    document.addEventListener('mousedown', function (evento) {
        var botao = evento.target.closest('.editor-rico-btn');
        if (!botao) { return; }

        evento.preventDefault();
        var area = areaDoControle(botao);
        if (area) { executarComando(area, botao.dataset.comando, botao.dataset.valor); }
    });

    document.addEventListener('change', function (evento) {
        var controle = evento.target;

        if (controle.matches && controle.matches('[data-editor-arquivo]')) {
            var areaArquivo = areaDoControle(controle);
            if (areaArquivo && controle.files[0]) { enviarImagem(areaArquivo, controle.files[0]); }
            controle.value = '';
            return;
        }

        var comando = controle.dataset ? controle.dataset.comando : null;
        if (!comando) { return; }

        var area = areaDoControle(controle);
        if (area) { executarComando(area, comando, controle.value); }
    });
})();
