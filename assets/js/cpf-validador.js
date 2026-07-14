(function () {
    function apenasDigitos(valor) {
        return (valor || '').replace(/\D/g, '');
    }

    // Mesmo algoritmo de App\Validation\CpfValidador::valido() (PHP) - so
    // aviso em tempo real, a validacao que realmente decide se salva
    // continua no servidor.
    function cpfValido(valor) {
        var cpf = apenasDigitos(valor);

        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }

        for (var posicaoDigito = 9; posicaoDigito <= 10; posicaoDigito++) {
            var soma = 0;

            for (var i = 0; i < posicaoDigito; i++) {
                soma += parseInt(cpf.charAt(i), 10) * ((posicaoDigito + 1) - i);
            }

            var resto = soma % 11;
            var digitoEsperado = resto < 2 ? 0 : 11 - resto;

            if (parseInt(cpf.charAt(posicaoDigito), 10) !== digitoEsperado) {
                return false;
            }
        }

        return true;
    }

    function formatarCpf(valor) {
        var digitos = apenasDigitos(valor).slice(0, 11);

        if (digitos.length > 9) {
            return digitos.slice(0, 3) + '.' + digitos.slice(3, 6) + '.' + digitos.slice(6, 9) + '-' + digitos.slice(9);
        }
        if (digitos.length > 6) {
            return digitos.slice(0, 3) + '.' + digitos.slice(3, 6) + '.' + digitos.slice(6);
        }
        if (digitos.length > 3) {
            return digitos.slice(0, 3) + '.' + digitos.slice(3);
        }
        return digitos;
    }

    function avisoDoCampo(campo) {
        var proximo = campo.nextElementSibling;

        if (proximo && proximo.classList.contains('campo-cpf-aviso')) {
            return proximo;
        }

        var aviso = document.createElement('small');
        aviso.className = 'campo-cpf-aviso';
        aviso.style.color = '#a3242c';
        aviso.style.display = 'none';
        aviso.style.marginTop = '0.25rem';
        aviso.textContent = 'CPF inválido.';
        campo.insertAdjacentElement('afterend', aviso);

        return aviso;
    }

    // Delegado no document (em vez de anexar listener campo a campo) porque
    // alguns campos de CPF (grupo_participantes, no formulario publico) sao
    // adicionados dinamicamente depois do carregamento da pagina - um
    // querySelectorAll() rodado uma vez so' na carga nao os pegaria.
    document.addEventListener('input', function (evento) {
        var campo = evento.target;

        if (!(campo instanceof HTMLInputElement) || !campo.classList.contains('campo-cpf-validar')) {
            return;
        }

        var posicaoCursor = campo.selectionStart;
        var comprimentoAntes = campo.value.length;
        campo.value = formatarCpf(campo.value);
        var diferenca = campo.value.length - comprimentoAntes;

        if (posicaoCursor !== null) {
            campo.setSelectionRange(posicaoCursor + diferenca, posicaoCursor + diferenca);
        }
    });

    document.addEventListener('focusout', function (evento) {
        var campo = evento.target;

        if (!(campo instanceof HTMLInputElement) || !campo.classList.contains('campo-cpf-validar')) {
            return;
        }

        var aviso = avisoDoCampo(campo);
        var preenchido = apenasDigitos(campo.value) !== '';
        var invalido = preenchido && !cpfValido(campo.value);
        aviso.style.display = invalido ? 'block' : 'none';
    });
})();
