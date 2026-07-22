/**
 * Fase 17 (Melhoria 1): redesenho da tela do avaliador - substitui
 * abas-avaliador.js (removido, nao existem mais abas). Cuida do indicador
 * de progresso e da confirmacao final antes de enviar (reaproveita o modal
 * generico de assets/js/modal.js).
 *
 * Progressivo: se o JS nao carregar, o formulario continua sendo um
 * <form>/<button type="submit"> normal - so' nao mostra a confirmacao.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('form-notas');

    if (!form) {
        return;
    }

    var camposNota = form.querySelectorAll('.campo-nota');
    var progresso = document.getElementById('progresso-avaliacao');

    function atualizarProgresso() {
        if (!progresso) {
            return;
        }

        var preenchidos = 0;

        camposNota.forEach(function (campo) {
            if (campo.value !== '') {
                preenchidos++;
            }
        });

        progresso.textContent = preenchidos + ' de ' + camposNota.length + ' critérios avaliados';
    }

    camposNota.forEach(function (campo) {
        campo.addEventListener('input', atualizarProgresso);
    });

    /**
     * Monta o resumo via DOM (createElement/textContent), nunca por
     * concatenacao de string - evita reinjetar valor de campo como HTML cru.
     */
    function construirResumo(aoConfirmar) {
        var container = document.createElement('div');
        var blocos = form.querySelectorAll('.criterio-bloco');

        blocos.forEach(function (bloco) {
            var nomeCriterio = bloco.getAttribute('data-criterio-nome');
            var campoNota = bloco.querySelector('.campo-nota');
            var campoFeedback = bloco.querySelector('textarea');
            var linha = document.createElement('p');
            var forte = document.createElement('strong');

            if (nomeCriterio) {
                forte.textContent = nomeCriterio + ': ';
                linha.appendChild(forte);
                linha.appendChild(document.createTextNode(campoNota && campoNota.value !== '' ? campoNota.value : '(sem nota)'));
            } else {
                forte.textContent = 'Feedback desta submissão';
                linha.appendChild(forte);
            }

            container.appendChild(linha);

            if (campoFeedback && campoFeedback.value.trim() !== '') {
                var textoFeedback = campoFeedback.value.trim();
                var resumoFeedback = document.createElement('p');
                resumoFeedback.className = 'campo-nota-escala';
                resumoFeedback.textContent = textoFeedback.length > 200 ? textoFeedback.slice(0, 200) + '…' : textoFeedback;
                container.appendChild(resumoFeedback);
            }
        });

        var aviso = document.createElement('p');
        var avisoEm = document.createElement('em');
        avisoEm.textContent = 'Depois que todos os critérios forem avaliados, esta submissão não poderá mais ser editada.';
        aviso.appendChild(avisoEm);
        container.appendChild(aviso);

        var acoes = document.createElement('div');

        var botaoConfirmar = document.createElement('button');
        botaoConfirmar.type = 'button';
        botaoConfirmar.className = 'btn btn-bordered';
        botaoConfirmar.textContent = 'Confirmar e enviar';
        botaoConfirmar.addEventListener('click', aoConfirmar);

        var botaoVoltar = document.createElement('button');
        botaoVoltar.type = 'button';
        botaoVoltar.className = 'btn-icone';
        botaoVoltar.style.marginLeft = '0.75rem';
        botaoVoltar.textContent = 'Voltar e revisar';
        botaoVoltar.addEventListener('click', fecharModal);

        acoes.appendChild(botaoConfirmar);
        acoes.appendChild(botaoVoltar);
        container.appendChild(acoes);

        return container;
    }

    var confirmado = false;

    /**
     * Um input "required" dentro de uma aba escondida (.criterio-painel
     * sem .ativo, display:none) pode fazer o navegador barrar o submit
     * sem mostrar nenhum balao de validacao visivel (elemento nao
     * renderizado) - avaliador clica "Salvar" e nada parece acontecer.
     * Antes de validar, ativa a aba do primeiro campo invalido.
     */
    function ativarAbaDoPrimeiroInvalido() {
        var primeiroInvalido = form.querySelector('.campo-nota:invalid');

        if (!primeiroInvalido) {
            return;
        }

        var painel = primeiroInvalido.closest('.criterio-painel');

        if (!painel) {
            return;
        }

        var indice = painel.dataset.criterioPainel;
        var aba = document.querySelector('[data-criterio-aba="' + indice + '"]');

        if (aba) {
            aba.click();
        }
    }

    form.addEventListener('submit', function (evento) {
        if (confirmado) {
            return;
        }

        ativarAbaDoPrimeiroInvalido();

        if (!form.reportValidity()) {
            return;
        }

        evento.preventDefault();

        var resumo = construirResumo(function () {
            confirmado = true;
            fecharModal();
            form.submit();
        });

        // resumo.innerHTML aqui e' seguro: toda a subarvore foi montada so'
        // com createElement/textContent (nunca concatenacao de string), que
        // ja escapa qualquer valor digitado pelo avaliador - serializar de
        // volta pra innerHTML so' devolve o HTML ja escapado, nao reabre
        // brecha de injecao.
        abrirModal('Revisar antes de enviar', resumo.innerHTML);

        // Os botoes de acao precisam ser religados: abrirModal() reescreve
        // #modal-conteudo via innerHTML, perdendo os listeners montados no
        // DOM original acima - religamos direto nos botoes ja renderizados.
        var modalConteudo = document.getElementById('modal-conteudo');
        var botaoConfirmarModal = modalConteudo.querySelector('.btn.btn-bordered');
        var botaoVoltarModal = modalConteudo.querySelector('.btn-icone');

        if (botaoConfirmarModal) {
            botaoConfirmarModal.addEventListener('click', function () {
                confirmado = true;
                fecharModal();
                form.submit();
            });
        }

        if (botaoVoltarModal) {
            botaoVoltarModal.addEventListener('click', fecharModal);
        }
    });

    atualizarProgresso();

    /**
     * Fase 19 (#10): abas por criterio - clique troca qual .criterio-painel
     * fica visivel. Nao muda nada do resto do arquivo (progresso, modal de
     * revisao, reportValidity()) - continuam operando sobre #form-notas
     * inteiro, abas visiveis ou nao (o navegador so' barra o submit se
     * algum .campo-nota "required" estiver vazio, mesmo que a aba dele
     * nao esteja ativa no momento do clique em "Salvar notas").
     */
    document.querySelectorAll('[data-criterio-aba]').forEach(function (aba) {
        aba.addEventListener('click', function () {
            var indice = aba.dataset.criterioAba;

            document.querySelectorAll('[data-criterio-aba]').forEach(function (botao) {
                botao.classList.toggle('ativo', botao === aba);
            });

            document.querySelectorAll('.criterio-painel').forEach(function (painel) {
                painel.classList.toggle('ativo', painel.dataset.criterioPainel === indice);
            });
        });
    });
});
