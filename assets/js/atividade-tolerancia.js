/**
 * Fase 47: converte os 5 percentuais fixos do select de tolerância de
 * presença efetiva (0/25/50/75/100) em minutos absolutos, a partir do
 * período real (Início/Fim) preenchido no formulário de atividade — sem
 * duração preenchida ainda (criação, campos vazios), os rótulos mostram só
 * o percentual, sem minutos.
 */
(function () {
    function calcularDuracaoMinutos(campoInicio, campoFim) {
        if (!campoInicio.value || !campoFim.value) {
            return null;
        }

        var diffMs = new Date(campoFim.value).getTime() - new Date(campoInicio.value).getTime();

        if (isNaN(diffMs) || diffMs <= 0) {
            return null;
        }

        return Math.round(diffMs / 60000);
    }

    function atualizarRotulos(selectTolerancia, duracaoMinutos) {
        Array.prototype.forEach.call(selectTolerancia.options, function (opcao) {
            var percentual = parseInt(opcao.value, 10);

            if (percentual === 0) {
                opcao.textContent = 'O momento do início da atividade (0%)';
                return;
            }

            if (percentual === 100) {
                opcao.textContent = 'Até o final da atividade (100%)';
                return;
            }

            if (duracaoMinutos === null) {
                opcao.textContent = percentual + '% do tempo planejado da atividade';
                return;
            }

            var minutos = Math.round(duracaoMinutos * (percentual / 100));
            opcao.textContent = 'Até ' + minutos + ' minuto' + (minutos === 1 ? '' : 's') + ' do início da atividade (' + percentual + '%)';
        });
    }

    /**
     * Fase 48 (correcao pos-teste de fumaca, achado real): navegacao pela
     * arvore lateral troca #conteudo-admin via AJAX sem nunca reexecutar
     * DOMContentLoaded nem os <script> de layout.php (ver
     * navegacao-arvore.js) - sem isso, os rotulos so' calculavam certo em
     * F5 direto na URL, nunca depois de navegar pela arvore. Roda direto
     * (o script ja carrega com "defer", entao o DOM ja esta pronto) e de
     * novo a cada 'conteudo-admin-atualizado'; dataset evita ligar o mesmo
     * <select> duas vezes.
     */
    function inicializar() {
        var selectTolerancia = document.getElementById('campo-atividade-tolerancia');

        if (!selectTolerancia || selectTolerancia.dataset.toleranciaInicializado === '1') {
            return;
        }

        var campoInicio = document.getElementById('campo-atividade-data-inicio');
        var campoFim = document.getElementById('campo-atividade-data-fim');

        if (!campoInicio || !campoFim) {
            return;
        }

        selectTolerancia.dataset.toleranciaInicializado = '1';

        function atualizar() {
            atualizarRotulos(selectTolerancia, calcularDuracaoMinutos(campoInicio, campoFim));
        }

        campoInicio.addEventListener('change', atualizar);
        campoFim.addEventListener('change', atualizar);
        atualizar();
    }

    inicializar();
    document.addEventListener('conteudo-admin-atualizado', inicializar);
})();
