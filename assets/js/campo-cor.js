/**
 * Fase 19 (#84 v5): campo de cor com hex editavel - o <input type="color">
 * nativo do navegador nao permite digitar um hex exato (o seletor visual
 * "arredonda" pra cor mais proxima que a paleta do sistema operacional
 * consegue mostrar). Este componente soma um <input type="text"> hex
 * editavel, sincronizado com o seletor visual pela mesma logica (mesmo
 * mecanismo do _campo-cor.blade.php do projeto LG Conecta, portado pra
 * vanilla JS). Delegacao em `document`, mesmo padrao dos demais
 * componentes desta fase (telas admin carregadas via arvore/modal).
 */
(function () {
    'use strict';

    function elementos(container) {
        return {
            picker: container.querySelector('[data-cor-picker]'),
            texto: container.querySelector('[data-cor-texto]'),
            valor: container.querySelector('[data-cor-valor]'),
            botaoSem: container.querySelector('[data-cor-sem]'),
            botaoLimpar: container.querySelector('[data-cor-limpar]'),
        };
    }

    function normalizarHex(valorDigitado) {
        var texto = valorDigitado.trim().toUpperCase();

        if (texto === '') {
            return '';
        }

        if (texto.charAt(0) !== '#') {
            texto = '#' + texto;
        }

        return /^#[0-9A-F]{6}$/.test(texto) ? texto : null;
    }

    function definirCor(container, cor) {
        var els = elementos(container);

        els.valor.value = cor;
        els.texto.value = cor;

        if (cor === '') {
            if (els.picker) { els.picker.hidden = true; }
            if (els.botaoSem) { els.botaoSem.hidden = false; }
            if (els.botaoLimpar) { els.botaoLimpar.hidden = true; }
            return;
        }

        if (els.picker) {
            els.picker.hidden = false;
            els.picker.value = cor;
        }

        if (els.botaoSem) { els.botaoSem.hidden = true; }
        if (els.botaoLimpar) { els.botaoLimpar.hidden = false; }
    }

    document.addEventListener('input', function (evento) {
        var container = evento.target.closest && evento.target.closest('[data-campo-cor]');
        if (!container) { return; }

        if (evento.target.matches('[data-cor-picker]')) {
            definirCor(container, evento.target.value.toUpperCase());
            return;
        }

        if (evento.target.matches('[data-cor-texto]')) {
            var normalizado = normalizarHex(evento.target.value);

            // null = ainda invalido/incompleto enquanto digita - nao
            // propaga pro hidden/picker ate ficar um hex de 6 digitos
            // valido (ou ficar vazio de proposito).
            if (normalizado === null) { return; }

            var els = elementos(container);
            els.valor.value = normalizado;

            if (normalizado !== '' && els.picker) {
                els.picker.hidden = false;
                els.picker.value = normalizado;
            }
        }
    });

    document.addEventListener('focusout', function (evento) {
        if (!evento.target.matches || !evento.target.matches('[data-cor-texto]')) { return; }

        var container = evento.target.closest('[data-campo-cor]');
        if (!container) { return; }

        var normalizado = normalizarHex(evento.target.value);

        if (normalizado === null) {
            // Texto invalido deixado no campo ao sair - volta pro
            // ultimo valor valido em vez de deixar lixo visivel.
            evento.target.value = elementos(container).valor.value;
        }
    });

    document.addEventListener('click', function (evento) {
        var container = evento.target.closest && evento.target.closest('[data-campo-cor]');
        if (!container) { return; }

        if (evento.target.closest('[data-cor-sem]')) {
            definirCor(container, container.dataset.corPadrao || '#F38123');
            return;
        }

        if (evento.target.closest('[data-cor-limpar]')) {
            definirCor(container, '');
        }
    });
})();
