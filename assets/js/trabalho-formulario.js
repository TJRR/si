/**
 * Reabertura da Fase 51 (achados da equipe de Teste Cego): comportamento em
 * tela do formulario de submissao de trabalho (trabalho/formulario.php).
 *
 * 1. Mostra so' a parte do formulario da forma de envio escolhida.
 * 2. Coautores: adiciona e remove blocos respeitando o limite vindo da
 *    configuracao do evento (data-max-coautores), nunca um numero fixo.
 * 3. Arquivos: confere o tamanho maximo ao escolher e avisa quando o nome nao
 *    tem uma das extensoes aceitas. So' o tamanho bloqueia aqui; a extensao
 *    e' um aviso porque o servidor decide pelo conteudo do arquivo (celulares
 *    entregam arquivos sem extensao). O servidor continua sendo quem decide.
 * 4. Depois de um envio recusado, rola ate o campo com problema e o foca
 *    (ou ate a caixa de erro, quando o erro nao pertence a um campo).
 */
(function () {
    'use strict';

    var formulario = document.getElementById('trabalho-formulario');

    if (!formulario) {
        return;
    }

    // 1. Forma de envio. Os 4 metodos ficam no HTML ao mesmo tempo; o de
    // "documento editavel" e o de "PDF" reaproveitam os MESMOS nomes de campo
    // (arquivo_avaliacao/arquivo_publicacao). "display: none" esconde da
    // tela mas nao tira o campo do envio, e o servidor ficaria com o do
    // metodo escondido (vazio) no lugar do arquivo real - por isso os campos
    // de toda secao que nao e' a escolhida sao desabilitados (campo
    // desabilitado nao vai no envio, e tambem nao trava a validacao nativa).
    var selecaoMetodo = document.getElementById('trabalho-metodo');
    var secoes = formulario.querySelectorAll('[data-metodo-secao]');

    function atualizarSecoes() {
        secoes.forEach(function (secao) {
            var ativa = selecaoMetodo && secao.getAttribute('data-metodo-secao') === selecaoMetodo.value;
            secao.style.display = ativa ? '' : 'none';
            secao.querySelectorAll('input, textarea, select').forEach(function (campo) {
                campo.disabled = !ativa;
            });
        });
    }

    if (selecaoMetodo) {
        selecaoMetodo.addEventListener('change', atualizarSecoes);
        atualizarSecoes();
    }

    // 2. Coautores.
    var areaCoautores = document.getElementById('trabalho-coautores');
    var listaCoautores = document.getElementById('trabalho-coautores-lista');
    var modeloCoautor = document.getElementById('trabalho-coautor-modelo');
    var botaoAdicionar = document.getElementById('trabalho-coautor-adicionar');
    var avisoLimite = document.getElementById('trabalho-coautores-limite');

    if (areaCoautores && listaCoautores && modeloCoautor && botaoAdicionar) {
        var maximoCoautores = parseInt(areaCoautores.getAttribute('data-max-coautores'), 10) || 0;

        var atualizarLimite = function () {
            var total = listaCoautores.querySelectorAll('.trabalho-coautor-item').length;
            var cheio = total >= maximoCoautores;

            botaoAdicionar.disabled = cheio;

            if (avisoLimite) {
                avisoLimite.hidden = !cheio;
            }
        };

        botaoAdicionar.addEventListener('click', function () {
            if (listaCoautores.querySelectorAll('.trabalho-coautor-item').length >= maximoCoautores) {
                return;
            }

            listaCoautores.appendChild(modeloCoautor.content.cloneNode(true));

            var itens = listaCoautores.querySelectorAll('.trabalho-coautor-item');
            var primeiroCampo = itens[itens.length - 1].querySelector('input');

            if (primeiroCampo) {
                primeiroCampo.focus();
            }

            atualizarLimite();
        });

        listaCoautores.addEventListener('click', function (evento) {
            if (evento.target.classList.contains('trabalho-coautor-remover')) {
                evento.target.closest('.trabalho-coautor-item').remove();
                atualizarLimite();
            }
        });

        atualizarLimite();
    }

    // 3. Arquivos.
    function limparMensagem(campo) {
        var rotulo = campo.closest('label');

        if (!rotulo) {
            return null;
        }

        rotulo.classList.remove('campo-com-erro');
        rotulo.querySelectorAll('.campo-erro-msg').forEach(function (mensagem) {
            mensagem.remove();
        });

        return rotulo;
    }

    function mostrarMensagem(campo, texto, bloqueia) {
        var rotulo = limparMensagem(campo);

        if (!rotulo) {
            return;
        }

        var mensagem = document.createElement('span');
        mensagem.className = 'campo-erro-msg' + (bloqueia ? '' : ' campo-aviso-msg');
        mensagem.textContent = texto;
        rotulo.appendChild(mensagem);

        if (bloqueia) {
            rotulo.classList.add('campo-com-erro');
        }
    }

    function conferirArquivo(campo) {
        limparMensagem(campo);

        if (!campo.files || campo.files.length === 0) {
            return;
        }

        var arquivo = campo.files[0];
        var maximoMb = parseFloat(campo.getAttribute('data-tamanho-max-mb'));

        if (maximoMb > 0 && arquivo.size > maximoMb * 1024 * 1024) {
            var tamanhoMb = (arquivo.size / (1024 * 1024)).toFixed(1).replace('.', ',');
            mostrarMensagem(campo, 'O arquivo "' + arquivo.name + '" tem ' + tamanhoMb + 'MB e o máximo aceito é ' + maximoMb + 'MB. Escolha outro arquivo.', true);
            campo.value = '';
            return;
        }

        var extensoes = (campo.getAttribute('data-extensoes') || '').split(',').filter(Boolean);
        var partes = arquivo.name.toLowerCase().split('.');
        var extensao = partes.length > 1 ? partes[partes.length - 1] : '';

        if (extensoes.length > 0 && extensoes.indexOf(extensao) === -1) {
            mostrarMensagem(campo, 'O nome "' + arquivo.name + '" não termina em ' + extensoes.map(function (e) { return '.' + e; }).join(', ') + '. O sistema confere o conteúdo do arquivo ao enviar; se não for do tipo aceito, o envio será recusado.', false);
        }
    }

    formulario.querySelectorAll('input[type="file"]').forEach(function (campo) {
        campo.addEventListener('change', function () {
            conferirArquivo(campo);
        });
    });

    // Quem corrige um campo que estava com erro tira o destaque dele na hora.
    formulario.addEventListener('input', function (evento) {
        var campo = evento.target;
        var rotulo = campo.closest ? campo.closest('label.campo-com-erro') : null;

        if (rotulo && campo.type !== 'file') {
            rotulo.classList.remove('campo-com-erro');
            rotulo.querySelectorAll('.campo-erro-msg').forEach(function (mensagem) {
                mensagem.remove();
            });
        }
    });

    // 4. Foco no primeiro problema.
    var alvo = formulario.querySelector('[data-foco-erro]');
    var caixaErro = document.getElementById('trabalho-erro-caixa');

    if (alvo) {
        alvo.scrollIntoView({ block: 'center' });
        alvo.focus({ preventScroll: true });
    } else if (caixaErro) {
        caixaErro.scrollIntoView({ block: 'center' });
        caixaErro.focus({ preventScroll: true });
    }
})();
