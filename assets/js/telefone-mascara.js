/**
 * Reabertura da Fase 51 (achado da equipe de Teste Cego): mascara de telefone
 * brasileiro com DDD que aceita os dois formatos - fixo de 10 digitos,
 * (12) 3456-7890, e celular de 11 digitos, (98) 76543-2100. Vale para todo
 * campo com a classe "campo-telefone", inclusive os criados depois da carga
 * da pagina (o evento e' delegado no documento, no mesmo estilo de
 * cpf-validador.js). O servidor confere de novo (formatarTelefoneBr(), em
 * app/helpers.php); esta mascara e' so' ajuda de digitacao.
 */
(function () {
    'use strict';

    function apenasDigitos(valor) {
        return (valor || '').replace(/\D/g, '');
    }

    function formatarTelefone(valor) {
        var digitos = apenasDigitos(valor).slice(0, 11);

        if (digitos.length === 0) {
            return '';
        }

        if (digitos.length <= 2) {
            return '(' + digitos;
        }

        var ddd = digitos.slice(0, 2);
        var numero = digitos.slice(2);

        if (numero.length <= 4) {
            return '(' + ddd + ') ' + numero;
        }

        if (digitos.length <= 10) {
            return '(' + ddd + ') ' + numero.slice(0, 4) + '-' + numero.slice(4);
        }

        return '(' + ddd + ') ' + numero.slice(0, 5) + '-' + numero.slice(5);
    }

    document.addEventListener('input', function (evento) {
        var campo = evento.target;

        if (!(campo instanceof HTMLInputElement) || !campo.classList.contains('campo-telefone')) {
            return;
        }

        var cursor = campo.selectionStart;
        var digitosAntesDoCursor = apenasDigitos(campo.value.slice(0, cursor)).length;
        var terminavaNoFim = cursor === campo.value.length;

        campo.value = formatarTelefone(campo.value);

        if (terminavaNoFim) {
            campo.setSelectionRange(campo.value.length, campo.value.length);
            return;
        }

        var contados = 0;
        var novaPosicao = campo.value.length;

        for (var i = 0; i < campo.value.length; i++) {
            if (/\d/.test(campo.value.charAt(i))) {
                contados++;
            }

            if (contados === digitosAntesDoCursor) {
                novaPosicao = i + 1;
                break;
            }
        }

        campo.setSelectionRange(novaPosicao, novaPosicao);
    });

    // Valor que ja veio preenchido (formulario reexibido depois de um erro,
    // ou preenchimento automatico do navegador) entra formatado tambem.
    document.querySelectorAll('.campo-telefone').forEach(function (campo) {
        campo.value = formatarTelefone(campo.value);
    });
})();
