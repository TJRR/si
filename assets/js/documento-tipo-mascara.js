/**
 * Fase 42 (correcao pos-teste de fumaca): o campo "Documento" da inscricao
 * publica do Evento (publico/evento_inscricao.php) e' texto livre - o TIPO
 * (RG/CPF/RNE/Passaporte) e' escolhido num campo configuravel separado
 * (Tipo de Documento de Identificação, EventoCampoInscricaoRepository;
 * marcado na tela com data-campo-tipo-documento). So' faz sentido
 * aplicar a mascara/validacao de CPF (assets/js/cpf-validador.js, ja usada
 * em outros formularios do sistema) quando "CPF" estiver selecionado - nos
 * demais tipos, o campo continua texto livre sem mascara.
 */
(function () {
    'use strict';

    function atualizar() {
        var campoDocumento = document.getElementById('campo-documento');
        var campoTipo = document.querySelector('[data-campo-tipo-documento]');

        if (!campoDocumento || !campoTipo) { return; }

        var ehCpf = campoTipo.value === 'CPF';
        campoDocumento.classList.toggle('campo-cpf-validar', ehCpf);

        if (!ehCpf) {
            var aviso = campoDocumento.nextElementSibling;
            if (aviso && aviso.classList.contains('campo-cpf-aviso')) {
                aviso.style.display = 'none';
            }
            return;
        }

        // O listener de mascara em tempo real (cpf-validador.js) so reage a
        // TECLAS novas digitadas (evento 'input') - um valor que ja estava
        // no campo (reload apos erro de validacao, ou numero digitado antes
        // de trocar o tipo para CPF) nunca seria reformatado sozinho.
        // Disparar um evento sintetico de 'input' aqui forca o mesmo
        // listener a rodar imediatamente sobre o valor ja existente (achado
        // real de teste da Fase 42: a mascara nunca aparecia depois de
        // escolher CPF). Desde a reabertura da Fase 51 a tela pede o tipo
        // antes do numero, mas o caso continua valendo.
        campoDocumento.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // Script incluido no fim do <body> (mesmo padrao de cpf-validador.js
    // nas demais telas publicas) - os campos ja existem no DOM neste ponto,
    // sem precisar esperar DOMContentLoaded.
    atualizar();

    document.addEventListener('change', function (evento) {
        if (evento.target.matches && evento.target.matches('[data-campo-tipo-documento]')) {
            atualizar();
        }
    });
})();
