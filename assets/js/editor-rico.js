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

    /**
     * Fase 19 (#88): o campo "Tamanho da fonte" nunca refletia o tamanho
     * atual da selecao - clicar nas setas sempre partia de vazio (o campo
     * number sem "value" incrementa a partir do min="8"), entao qualquer
     * ajuste "voltava pro 8". Le o font-size computado no elemento onde
     * esta o cursor/selecao e preenche o campo, pra que as setas incrementem
     * a partir do tamanho real, nao de vazio.
     */
    function atualizarCampoTamanho(area) {
        var caixa = area.closest('.editor-rico');
        var campoTamanho = caixa ? caixa.querySelector('[data-comando="tamanho"]') : null;

        if (!campoTamanho) { return; }

        var selecao = window.getSelection();
        var no = selecao.rangeCount > 0 ? selecao.anchorNode : null;

        if (!no || !area.contains(no)) { return; }

        var elemento = no.nodeType === 1 ? no : no.parentElement;

        if (!elemento) { return; }

        var tamanhoAtual = parseInt(window.getComputedStyle(elemento).fontSize, 10);
        campoTamanho.value = isNaN(tamanhoAtual) ? '' : tamanhoAtual;
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

    /**
     * Fase 19 (#88): "selectionchange" substitui os antigos listeners de
     * mouseup/keyup na area - aqueles so' capturavam a selecao quando o
     * evento acontecia dentro do proprio contenteditable, e deixavam
     * buracos reais (duplo/triplo clique pra selecionar palavra/paragrafo,
     * Ctrl+A, selecao que termina fora da area) que faziam a selecao salva
     * ficar desatualizada bem antes do clique na barra de ferramentas -
     * exatamente o padrao relatado ("clico em negrito/cor/tamanho com texto
     * selecionado e nada acontece"). selectionchange dispara pra qualquer
     * mudanca de selecao, de qualquer origem, incluindo a que o proprio
     * execCommand() provoca ao aplicar um comando - o que tambem mantem
     * rangesSalvos sempre atualizado entre comandos encadeados (negrito,
     * depois cor, depois tamanho, sem re-selecionar o texto entre um e
     * outro).
     */
    document.addEventListener('selectionchange', function () {
        var selecao = window.getSelection();

        if (selecao.rangeCount === 0) { return; }

        var no = selecao.anchorNode;
        var elemento = no ? (no.nodeType === 1 ? no : no.parentElement) : null;
        var area = elemento ? elemento.closest('[data-editor-area]') : null;

        if (area) {
            salvarSelecao(area);
            atualizarCampoTamanho(area);
        }
    });

    /**
     * Fase 19 (#91): o campo de tamanho da fonte fica dentro do <form> do
     * bloco (slide/banner/etc.) - Enter nele disparava o submit implicito
     * do formulario inteiro (comportamento padrao do HTML pra <input>
     * dentro de <form> com botao de enviar), o que salvava o formulario
     * antes da hora. Enter agora so' aplica o tamanho digitado.
     */
    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Enter') { return; }

        var campo = evento.target.closest && evento.target.closest('.editor-rico-tamanho');
        if (!campo) { return; }

        evento.preventDefault();
        campo.dispatchEvent(new Event('change', { bubbles: true }));
    });

    document.addEventListener('input', function (evento) {
        var area = evento.target.closest && evento.target.closest('[data-editor-area]');
        if (area) { sincronizarHidden(area); }

        var codigo = evento.target.closest && evento.target.closest('[data-editor-codigo]');
        if (codigo) {
            var hiddenCodigo = codigo.closest('.editor-rico').querySelector('[data-editor-hidden]');
            hiddenCodigo.value = codigo.value;
        }
    });

    document.addEventListener('mousedown', function (evento) {
        var botao = evento.target.closest('.editor-rico-btn');
        // O botao de alternar modo nao aplica nenhum comando de
        // formatacao (nao depende de selecao) - tem o proprio handler no
        // 'click' abaixo, sem precisar do preventDefault de foco que os
        // outros botoes da barra usam.
        if (!botao || botao.hasAttribute('data-editor-alternar-modo')) { return; }

        evento.preventDefault();
        var area = areaDoControle(botao);
        if (area) { executarComando(area, botao.dataset.comando, botao.dataset.valor); }
    });

    /**
     * Fase 19 (#84 v5): alterna entre o contenteditable (modo visual) e um
     * <textarea> com o HTML cru (modo codigo) - permite corrigir na mao
     * problemas que o WYSIWYG sozinho nao resolve (ex.: uma referencia de
     * imagem quebrada colada de outro site, ou um elemento com
     * overflow/altura fixa que atrapalha o layout aqui).
     */
    document.addEventListener('click', function (evento) {
        var botaoAlternar = evento.target.closest('[data-editor-alternar-modo]');
        if (!botaoAlternar) { return; }

        var container = botaoAlternar.closest('.editor-rico');
        var area = container.querySelector('[data-editor-area]');
        var codigo = container.querySelector('[data-editor-codigo]');
        var indoParaCodigo = codigo.hidden;

        if (indoParaCodigo) {
            codigo.value = area.innerHTML;
            area.hidden = true;
            codigo.hidden = false;
        } else {
            area.innerHTML = codigo.value;
            codigo.hidden = true;
            area.hidden = false;
        }

        sincronizarHidden(area);
        botaoAlternar.classList.toggle('ativo', indoParaCodigo);
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
