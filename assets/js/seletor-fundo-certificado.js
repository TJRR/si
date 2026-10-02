/**
 * Fase 59: seletor de plano de fundo do certificado.
 *
 * Abre a Biblioteca de midia numa janela sobreposta (abrirModal, de
 * modal.js), começando na pasta "Fundo Certificados", deixa escolher uma
 * imagem, enviar uma arte nova ali mesmo, trocar o fundo por uma cor ou
 * limpar os dois.
 *
 * Os tratadores sao delegados no documento, e nao ligados aos elementos: o
 * conteudo da janela e' montado por innerHTML a cada abertura, e o painel
 * administrativo troca de tela sem recarregar a pagina (navegacao em arvore),
 * entao nenhum elemento vive o suficiente para receber tratador proprio.
 *
 * Nada aqui grava configuracao: o seletor so' mexe nos dois campos ocultos do
 * formulario, e quem salva continua sendo o botao de salvar da tela.
 */
(function () {
    var seletorAtivo = null;
    var escolhidaUrl = null;

    function url(rota) {
        return (window.SI_BASE_PATH || '') + '/index.php?r=' + rota;
    }

    /**
     * Escapa para uso em atributo HTML, e nao so' em texto: o titulo da
     * imagem e' escrito pelo Administrador e entra em title="...", entao as
     * aspas tambem precisam sair.
     */
    function escapar(texto) {
        return String(texto === null || texto === undefined ? '' : texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function aplicar(seletor, imagem, cor, texto) {
        var amostra = seletor.querySelector('[data-fundo-amostra]');
        var campoUrl = seletor.querySelector('[data-fundo-valor-url]');
        var campoCor = seletor.querySelector('[data-fundo-valor-cor]');
        var rotulo = seletor.querySelector('[data-fundo-texto]');

        campoUrl.value = imagem || '';
        campoCor.value = cor || '';

        if (imagem) {
            amostra.style.backgroundImage = "url('" + imagem + "')";
            amostra.style.backgroundColor = '';
        } else {
            amostra.style.backgroundImage = '';
            amostra.style.backgroundColor = cor || '#ffffff';
        }

        if (rotulo) {
            rotulo.textContent = texto;
        }
    }

    function montarConteudo(dados) {
        var html = '<div class="galeria-fundo">';

        html += '<p><label>Pasta da Biblioteca de mídia: <select data-fundo-pasta>';

        dados.pastas.forEach(function (pasta) {
            var marcada = String(pasta.id) === String(dados.pastaAtual) ? ' selected' : '';
            html += '<option value="' + pasta.id + '"' + marcada + '>' + escapar(pasta.nome) + '</option>';
        });

        html += '</select></label></p>';

        if (!dados.imagens.length) {
            html += '<p>Nenhuma imagem nesta pasta. Envie uma arte abaixo ou escolha outra pasta.</p>';
        } else {
            html += '<div class="galeria-fundo-grade">';

            dados.imagens.forEach(function (imagem) {
                var marcada = imagem.url === escolhidaUrl ? ' escolhida' : '';
                html += '<button type="button" class="galeria-fundo-item' + marcada + '" data-fundo-imagem="'
                    + escapar(imagem.url) + '" title="' + escapar(imagem.titulo) + '">'
                    + '<img src="' + escapar(imagem.url) + '" alt="">'
                    + '<span>' + escapar(imagem.titulo) + '</span>'
                    + '</button>';
            });

            html += '</div>';
        }

        html += '<p class="galeria-fundo-envio">'
            + '<label>Enviar uma arte nova <input type="file" accept="image/*" data-fundo-arquivo></label>'
            + '<button type="button" class="btn-acao" data-fundo-enviar>Enviar</button>'
            + '<span data-fundo-aviso></span>'
            + '</p>';

        html += '<p><button type="button" data-fundo-confirmar>Usar esta imagem</button>'
            + ' <button type="button" class="btn-acao" onclick="fecharModal()">Cancelar</button></p>';

        html += '</div>';

        return html;
    }

    function abrirJanela() {
        var evento = seletorAtivo.getAttribute('data-fundo-evento');
        var atual = seletorAtivo.querySelector('[data-fundo-valor-url]').value;
        escolhidaUrl = atual || null;

        carregar(url('certificados/fundos/' + evento));
    }

    function carregar(endereco) {
        fetch(endereco, { credentials: 'same-origin' })
            .then(function (resposta) {
                return resposta.ok ? resposta.json() : Promise.reject();
            })
            .then(function (dados) {
                abrirModal('Plano de fundo do certificado', montarConteudo(dados));
            })
            .catch(function () {
                abrirModal(
                    'Plano de fundo do certificado',
                    '<p>Não foi possível abrir a Biblioteca de mídia agora. Recarregue a página e tente outra vez.</p>'
                );
            });
    }

    function enviar(janela) {
        var campo = janela.querySelector('[data-fundo-arquivo]');
        var aviso = janela.querySelector('[data-fundo-aviso]');
        var evento = seletorAtivo.getAttribute('data-fundo-evento');

        if (!campo || !campo.files || !campo.files.length) {
            aviso.textContent = 'Escolha o arquivo de imagem a enviar.';
            return;
        }

        var dados = new FormData();
        dados.append('arquivo', campo.files[0]);
        aviso.textContent = 'Enviando…';

        fetch(url('certificados/enviarFundo/' + evento), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': window.SI_CSRF_TOKEN || '' },
            body: dados
        })
            .then(function (resposta) {
                return resposta.json().then(function (corpo) {
                    return resposta.ok ? corpo : Promise.reject(corpo);
                });
            })
            .then(function (corpo) {
                // A arte enviada ja' fica escolhida, como o dono pediu: a
                // janela recarrega mostrando a pasta de fundos com ela
                // marcada.
                escolhidaUrl = corpo.url;
                carregar(url('certificados/fundos/' + evento));
            })
            .catch(function (corpo) {
                aviso.textContent = corpo && corpo.erro
                    ? corpo.erro
                    : 'Não foi possível enviar a imagem agora.';
            });
    }

    document.addEventListener('click', function (evento) {
        var alvo = evento.target;
        var botao = alvo.closest ? alvo.closest('[data-fundo-escolher], [data-fundo-limpar], [data-fundo-imagem], [data-fundo-confirmar], [data-fundo-enviar]') : null;

        if (!botao) {
            return;
        }

        if (botao.hasAttribute('data-fundo-escolher')) {
            seletorAtivo = botao.closest('[data-seletor-fundo]');
            abrirJanela();
            return;
        }

        if (botao.hasAttribute('data-fundo-limpar')) {
            aplicar(botao.closest('[data-seletor-fundo]'), '', '', 'Sem imagem e sem cor: folha branca.');
            return;
        }

        if (botao.hasAttribute('data-fundo-imagem')) {
            escolhidaUrl = botao.getAttribute('data-fundo-imagem');
            botao.parentNode.querySelectorAll('.galeria-fundo-item').forEach(function (item) {
                item.classList.remove('escolhida');
            });
            botao.classList.add('escolhida');
            return;
        }

        if (botao.hasAttribute('data-fundo-enviar')) {
            enviar(botao.closest('.galeria-fundo'));
            return;
        }

        if (botao.hasAttribute('data-fundo-confirmar') && seletorAtivo) {
            if (!escolhidaUrl) {
                return;
            }

            aplicar(seletorAtivo, escolhidaUrl, '', 'Imagem escolhida.');
            fecharModal();
        }
    });

    document.addEventListener('change', function (evento) {
        var alvo = evento.target;

        if (alvo.hasAttribute && alvo.hasAttribute('data-fundo-pasta') && seletorAtivo) {
            carregar(url('certificados/fundos/' + seletorAtivo.getAttribute('data-fundo-evento')) + '&pasta=' + encodeURIComponent(alvo.value));
            return;
        }

        // Escolher uma cor troca o fundo por ela: cor e imagem nunca valem
        // juntas, e a imagem e' quem o certificado mostraria.
        if (alvo.hasAttribute && alvo.hasAttribute('data-fundo-cor')) {
            aplicar(alvo.closest('[data-seletor-fundo]'), '', alvo.value, 'Fundo em cor, sem imagem.');
        }
    });
})();
