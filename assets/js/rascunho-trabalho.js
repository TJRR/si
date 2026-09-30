/**
 * Fase 57 (reabertura): rascunho do formulario de submissao de Trabalhos
 * (trabalho/formulario.php), guardado SO' no navegador. Serve a dois casos:
 *
 * - o visitante preenche antes de entrar, clica em "Entrar para enviar", faz
 *   o cadastro ou a entrada e, ao voltar, encontra o que preencheu, arquivos
 *   inclusive;
 * - o servidor devolve o formulario com erro de validacao: os textos voltam
 *   pelo proprio servidor e os arquivos, que o navegador nao reenvia, sao
 *   recolocados daqui.
 *
 * Nada chega ao servidor antes da autenticacao, e o envio do formulario
 * NUNCA e' interceptado (nenhum preventDefault no "submit"): o rascunho e'
 * gravado continuamente, ao digitar e ao escolher arquivo. Qualquer falha
 * daqui termina em silencio e deixa a tela como o servidor a montou.
 *
 * Onde fica cada coisa:
 * - IndexedDB (banco BANCO): um registro de texto por aba e evento e um
 *   registro por arquivo (aba, evento, forma de envio, campo). A validade e'
 *   a do registro de texto, 2 horas desde a ultima gravacao; os arquivos
 *   acompanham.
 * - sessionStorage (morre com a aba): identificador da aba, assinatura de
 *   cada arquivo escolhido (nome, tamanho, data) e o sinal "enviando".
 * - localStorage: marcador de que existe rascunho neste navegador e sinal de
 *   limpeza pendente.
 *
 * O arquivo e' carregado em TODA pagina (layout.php), porque a limpeza
 * (rascunho vencido, saida do sistema) precisa acontecer fora do formulario
 * tambem. Sem marcador e sem sinal de limpeza, a parte global termina sem
 * abrir o banco: nas telas do Concurso ela nao faz nada.
 *
 * Os textos mostrados a pessoa ficam na tela (trabalho/formulario.php), em
 * elementos ocultos e atributos data-*; aqui so' se mostra e se preenche.
 */
(function () {
    'use strict';

    if (typeof Promise !== 'function') {
        return;
    }

    var BANCO = 'si_rascunho_trabalho';
    var ARMAZEM = 'itens';
    var VALIDADE_MS = 2 * 60 * 60 * 1000;
    var TETO_BANCO_MS = 10000;
    var TETO_OPERACAO_MS = 45000;
    var TETO_RESTAURACAO_MS = 60000;
    var ESPERA_TEXTO_MS = 400;
    var TETO_ENTRAR_TEXTO_MS = 3000;
    var TETO_ENTRAR_ARQUIVO_MS = 60000;

    var CHAVE_MARCADOR = 'si_rascunho_trabalho_marcador';
    var CHAVE_LIMPAR = 'si_rascunho_trabalho_limpar';
    var CHAVE_ABA = 'si_rascunho_trabalho_aba';
    var CHAVE_ASSINATURAS = 'si_rascunho_trabalho_arquivos';
    var CHAVE_ENVIANDO = 'si_rascunho_trabalho_enviando';

    function nada() {}

    // Armazenamento simples. Em navegacao privada qualquer um destes pode
    // lancar excecao; a resposta e' sempre "nao ha' nada guardado".
    function lerDe(armazenamento, chave) {
        try {
            return window[armazenamento].getItem(chave);
        } catch (erro) {
            return null;
        }
    }

    function gravarEm(armazenamento, chave, valor) {
        try {
            window[armazenamento].setItem(chave, valor);
            return true;
        } catch (erro) {
            return false;
        }
    }

    function removerDe(armazenamento, chave) {
        try {
            window[armazenamento].removeItem(chave);
        } catch (erro) {
            // Sem armazenamento, nao ha' o que remover.
        }
    }

    function lerAssinaturas() {
        try {
            var bruto = lerDe('sessionStorage', CHAVE_ASSINATURAS);
            var assinaturas = bruto ? JSON.parse(bruto) : {};

            return assinaturas && typeof assinaturas === 'object' ? assinaturas : {};
        } catch (erro) {
            return {};
        }
    }

    function gravarAssinaturas(assinaturas) {
        return gravarEm('sessionStorage', CHAVE_ASSINATURAS, JSON.stringify(assinaturas));
    }

    function chaveAssinatura(evento, metodo, campo) {
        return evento + '|' + metodo + '|' + campo;
    }

    function limparAssinaturasDoEvento(evento) {
        var assinaturas = lerAssinaturas();

        Object.keys(assinaturas).forEach(function (chave) {
            if (chave.indexOf(evento + '|') === 0) {
                delete assinaturas[chave];
            }
        });

        gravarAssinaturas(assinaturas);
    }

    function chaveTexto(aba, evento) {
        return 'texto|' + aba + '|' + evento;
    }

    function chaveArquivo(aba, evento, metodo, campo) {
        return 'arquivo|' + aba + '|' + evento + '|' + metodo + '|' + campo;
    }

    function aleatorio() {
        try {
            var bytes = new Uint8Array(12);
            window.crypto.getRandomValues(bytes);

            return Array.prototype.map.call(bytes, function (valor) {
                return ('0' + valor.toString(16)).slice(-2);
            }).join('');
        } catch (erro) {
            return String(Date.now()) + String(Math.floor(Math.random() * 1e9));
        }
    }

    // Promessa de banco que nunca responde nao rejeita sozinha: todo uso
    // passa por aqui, para a tela nunca ficar esperando.
    function comTeto(promessa, milissegundos) {
        return new Promise(function (resolver, rejeitar) {
            var temporizador = setTimeout(function () {
                rejeitar(new Error('teto'));
            }, milissegundos);

            promessa.then(function (valor) {
                clearTimeout(temporizador);
                resolver(valor);
            }, function (erro) {
                clearTimeout(temporizador);
                rejeitar(erro);
            });
        });
    }

    // A conexao e' aberta e fechada a cada operacao: conexao mantida aberta
    // numa aba trava a limpeza pedida por outra.
    function abrirBanco() {
        return new Promise(function (resolver, rejeitar) {
            var desistiu = false;
            var pedido;
            var temporizador = setTimeout(function () {
                desistiu = true;
                rejeitar(new Error('teto'));
            }, TETO_BANCO_MS);

            try {
                pedido = window.indexedDB.open(BANCO, 1);
            } catch (erro) {
                clearTimeout(temporizador);
                rejeitar(erro);
                return;
            }

            pedido.onupgradeneeded = function () {
                if (!pedido.result.objectStoreNames.contains(ARMAZEM)) {
                    pedido.result.createObjectStore(ARMAZEM, { keyPath: 'chave' });
                }
            };

            pedido.onsuccess = function () {
                if (desistiu) {
                    pedido.result.close();
                    return;
                }

                clearTimeout(temporizador);
                resolver(pedido.result);
            };

            pedido.onerror = function () {
                if (!desistiu) {
                    clearTimeout(temporizador);
                    rejeitar(pedido.error || new Error('abrir'));
                }
            };

            pedido.onblocked = function () {
                if (!desistiu) {
                    clearTimeout(temporizador);
                    rejeitar(new Error('bloqueado'));
                }
            };
        });
    }

    // Uma transacao por chamada. "acao" recebe o armazem, faz os pedidos e
    // devolve o objeto em que guardou o que leu; a promessa resolve com ele
    // quando a transacao termina.
    function operar(modo, acao) {
        return comTeto(abrirBanco().then(function (banco) {
            return new Promise(function (resolver, rejeitar) {
                var saida;
                var transacao;

                try {
                    transacao = banco.transaction(ARMAZEM, modo);
                    saida = acao(transacao.objectStore(ARMAZEM));
                } catch (erro) {
                    banco.close();
                    rejeitar(erro);
                    return;
                }

                transacao.oncomplete = function () {
                    banco.close();
                    resolver(saida);
                };

                transacao.onerror = function () {
                    banco.close();
                    rejeitar(transacao.error || new Error('transacao'));
                };

                transacao.onabort = function () {
                    banco.close();
                    rejeitar(transacao.error || new Error('transacao'));
                };
            });
        }), TETO_OPERACAO_MS);
    }

    function lerRegistro(chave) {
        return operar('readonly', function (armazem) {
            var saida = { valor: null };
            var pedido = armazem.get(chave);

            pedido.onsuccess = function () {
                saida.valor = pedido.result || null;
            };

            return saida;
        }).then(function (saida) {
            return saida.valor;
        });
    }

    // So' as chaves: listar com os valores traria os arquivos inteiros para
    // a memoria a cada carga de pagina.
    function listarChaves() {
        return operar('readonly', function (armazem) {
            var saida = { chaves: [] };
            var pedido;

            if (typeof armazem.getAllKeys === 'function') {
                pedido = armazem.getAllKeys();
                pedido.onsuccess = function () {
                    saida.chaves = pedido.result || [];
                };
            } else {
                pedido = armazem.openKeyCursor();
                pedido.onsuccess = function () {
                    var cursor = pedido.result;

                    if (cursor) {
                        saida.chaves.push(cursor.key);
                        cursor['continue']();
                    }
                };
            }

            return saida;
        }).then(function (saida) {
            return saida.chaves.map(String);
        });
    }

    function gravarRegistro(registro) {
        return operar('readwrite', function (armazem) {
            armazem.put(registro);
        });
    }

    function apagarChaves(chaves) {
        if (chaves.length === 0) {
            return Promise.resolve();
        }

        return operar('readwrite', function (armazem) {
            chaves.forEach(function (chave) {
                armazem['delete'](chave);
            });
        });
    }

    function atualizarMarcador() {
        return listarChaves().then(function (chaves) {
            var haRascunho = chaves.some(function (chave) {
                return chave.indexOf('teste|') !== 0;
            });

            if (haRascunho) {
                gravarEm('localStorage', CHAVE_MARCADOR, '1');
            } else {
                removerDe('localStorage', CHAVE_MARCADOR);
            }
        });
    }

    // Esvaziar e' sempre limpar o armazem, nunca apagar o banco: apagar o
    // banco fica bloqueado enquanto outra aba tiver conexao aberta.
    function esvaziarTudo() {
        removerDe('sessionStorage', CHAVE_ASSINATURAS);
        removerDe('sessionStorage', CHAVE_ENVIANDO);

        return operar('readwrite', function (armazem) {
            armazem.clear();
        }).then(function () {
            removerDe('localStorage', CHAVE_MARCADOR);
            removerDe('localStorage', CHAVE_LIMPAR);
        });
    }

    function apagarRascunhoDaAba(evento) {
        var aba = lerDe('sessionStorage', CHAVE_ABA);

        limparAssinaturasDoEvento(evento);

        if (!aba) {
            return Promise.resolve();
        }

        var texto = chaveTexto(aba, evento);
        var prefixoArquivo = 'arquivo|' + aba + '|' + evento + '|';

        return listarChaves().then(function (chaves) {
            return apagarChaves(chaves.filter(function (chave) {
                return chave === texto || chave.indexOf(prefixoArquivo) === 0;
            }));
        }).then(atualizarMarcador);
    }

    // Rascunho vencido de QUALQUER aba sai aqui, a cada carga de pagina do
    // sistema. Arquivo sem o registro de texto do mesmo rascunho e' sobra e
    // sai junto.
    function purgarVencidos() {
        return listarChaves().then(function (chaves) {
            var textos = chaves.filter(function (chave) {
                return chave.indexOf('texto|') === 0;
            });

            return Promise.all(textos.map(lerRegistro)).then(function (registros) {
                var agora = Date.now();
                var validos = {};

                registros.forEach(function (registro) {
                    if (registro && registro.gravado_em + VALIDADE_MS >= agora) {
                        validos[registro.aba + '|' + registro.evento] = true;
                    }
                });

                var vencidas = chaves.filter(function (chave) {
                    var partes = chave.split('|');

                    if (partes[0] === 'teste') {
                        return Number(partes[1]) + 60000 < agora;
                    }

                    return !validos[partes[1] + '|' + partes[2]];
                });

                return apagarChaves(vencidas);
            });
        }).then(atualizarMarcador);
    }

    // Saida do sistema: o sinal de limpeza e' gravado de forma sincrona,
    // para a pagina seguinte concluir o que esta nao tiver tempo de fazer. A
    // navegacao nunca e' segurada.
    function ligarSaida() {
        document.addEventListener('click', function (evento) {
            try {
                var alvo = evento.target && evento.target.closest ? evento.target.closest('a[href*="auth/logout"]') : null;

                if (!alvo || lerDe('localStorage', CHAVE_MARCADOR) !== '1') {
                    return;
                }

                gravarEm('localStorage', CHAVE_LIMPAR, '1');
                esvaziarTudo().then(nada, nada);
            } catch (erro) {
                // A saida segue normalmente.
            }
        });
    }

    function parteGlobal() {
        ligarSaida();

        // A tela do trabalho so' descarta o rascunho quando o envio acabou
        // de acontecer nesta aba (sinal "enviando"); abrir um trabalho
        // anterior por "Meus trabalhos" nao apaga um rascunho em andamento.
        var telaEnviado = document.querySelector('[data-rascunho-enviado-evento]');
        var eventoEnviado = null;

        if (telaEnviado) {
            if (lerDe('sessionStorage', CHAVE_ENVIANDO) === telaEnviado.getAttribute('data-rascunho-enviado-evento')) {
                eventoEnviado = telaEnviado.getAttribute('data-rascunho-enviado-evento');
            }

            removerDe('sessionStorage', CHAVE_ENVIANDO);
        }

        var limpar = lerDe('localStorage', CHAVE_LIMPAR) === '1';
        var marcador = lerDe('localStorage', CHAVE_MARCADOR) === '1';

        if (!limpar && !marcador) {
            return Promise.resolve();
        }

        if (!window.indexedDB) {
            removerDe('localStorage', CHAVE_LIMPAR);
            removerDe('localStorage', CHAVE_MARCADOR);

            return Promise.resolve();
        }

        if (limpar) {
            return esvaziarTudo();
        }

        var cadeia = Promise.resolve();
        var telaDescartar = document.querySelector('[data-rascunho-descartar-evento]');

        if (eventoEnviado !== null) {
            cadeia = cadeia.then(function () {
                return apagarRascunhoDaAba(eventoEnviado);
            }).then(nada, nada);
        }

        if (telaDescartar) {
            cadeia = cadeia.then(function () {
                return apagarRascunhoDaAba(telaDescartar.getAttribute('data-rascunho-descartar-evento'));
            }).then(nada, nada);
        }

        return cadeia.then(purgarVencidos);
    }

    function parteFormulario() {
        // O sinal "enviando" so' vale entre o envio e a tela do trabalho.
        // Toda carga do formulario, em qualquer modo, o apaga: depois de um
        // erro de validacao ele nao pode sobrar.
        if (document.querySelector('[data-rascunho-tela="formulario"]')) {
            removerDe('sessionStorage', CHAVE_ENVIANDO);
        }

        var formulario = document.getElementById('trabalho-formulario');
        var evento = formulario ? formulario.getAttribute('data-rascunho-evento') : null;

        if (!formulario || !evento) {
            return;
        }

        var modo = formulario.getAttribute('data-rascunho-modo') || 'novo';
        var contaAtual = formulario.getAttribute('data-rascunho-conta') || '';
        var campoErro = formulario.getAttribute('data-campo-erro') || '';
        var seletorMetodo = document.getElementById('trabalho-metodo');
        var botaoEntrar = document.getElementById('trabalho-entrar-enviar');

        var pronto = false;
        var restaurando = false;
        var gravacaoLigada = true;
        var fila = Promise.resolve();
        var arquivosEmCurso = 0;
        var temporizadorTexto = null;
        var adotar = false;

        function mostrar(id) {
            var elemento = document.getElementById(id);

            if (elemento) {
                elemento.hidden = false;
            }
        }

        function disparar(campo, tipo) {
            campo.dispatchEvent(new Event(tipo, { bubbles: true }));
        }

        function idDaAba() {
            var aba = lerDe('sessionStorage', CHAVE_ABA);

            if (!aba) {
                aba = aleatorio();

                if (!gravarEm('sessionStorage', CHAVE_ABA, aba)) {
                    return null;
                }
            }

            return aba;
        }

        function metodoDoCampo(campo) {
            var secao = campo.closest('[data-metodo-secao]');

            return secao ? secao.getAttribute('data-metodo-secao') : '';
        }

        function camposDeArquivoAtivos() {
            return Array.prototype.filter.call(formulario.querySelectorAll('input[type="file"]'), function (campo) {
                return !campo.disabled;
            });
        }

        function temOpcao(lista, valor) {
            return Array.prototype.some.call(lista.options, function (opcao) {
                return opcao.value === valor;
            });
        }

        function valorPadraoDaLista(lista) {
            var padrao = lista.options.length > 0 ? lista.options[0].value : '';

            Array.prototype.forEach.call(lista.options, function (opcao) {
                if (opcao.defaultSelected) {
                    padrao = opcao.value;
                }
            });

            return padrao;
        }

        // Campo que entra no retrato de texto. Ficam de fora: o codigo de
        // protecao do formulario, campos ocultos, arquivos, campos
        // desabilitados (secao de outra forma de envio), a forma de envio,
        // os coautores e as declaracoes, que tem tratamento proprio.
        function campoDeTexto(campo) {
            var tipo = (campo.type || '').toLowerCase();

            if (!campo.name || campo.disabled || campo === seletorMetodo) {
                return false;
            }

            if (campo.name === 'csrf_token' || campo.name === 'termos_aceitos[]' || campo.name.indexOf('coautor_') === 0) {
                return false;
            }

            if (['hidden', 'file', 'submit', 'button', 'reset', 'image', 'password', 'checkbox', 'radio'].indexOf(tipo) !== -1) {
                return false;
            }

            return campo.tagName === 'INPUT' || campo.tagName === 'TEXTAREA' || campo.tagName === 'SELECT';
        }

        // Le a pagina no momento da gravacao, e nao o valor do evento: as
        // mascaras de CPF e telefone rodam depois do tratador do formulario.
        function retratar(aba) {
            var campos = {};
            var coautores = [];
            var termos = [];

            Array.prototype.forEach.call(formulario.elements, function (campo) {
                if (campoDeTexto(campo)) {
                    campos[campo.name] = campo.value;
                }
            });

            // Coautores sao posicionais (coautor_nome[]): guarda-se a lista
            // de blocos, na ordem da tela.
            Array.prototype.forEach.call(formulario.querySelectorAll('.trabalho-coautor-item'), function (bloco) {
                var valores = {};

                Array.prototype.forEach.call(bloco.querySelectorAll('input[name]'), function (campo) {
                    valores[campo.name] = campo.value;
                });

                coautores.push(valores);
            });

            Array.prototype.forEach.call(formulario.querySelectorAll('input[type="checkbox"][name="termos_aceitos[]"]'), function (caixa) {
                if (caixa.checked) {
                    termos.push(caixa.value);
                }
            });

            return {
                chave: chaveTexto(aba, evento),
                aba: aba,
                evento: evento,
                conta: contaAtual,
                gravado_em: Date.now(),
                metodo: seletorMetodo && seletorMetodo.tagName === 'SELECT' ? seletorMetodo.value : '',
                campos: campos,
                coautores: coautores,
                termos: termos
            };
        }

        // As gravacoes correm uma de cada vez, na ordem em que foram
        // pedidas; uma que falha nao impede as seguintes.
        function enfileirar(tarefa) {
            fila = fila.then(tarefa).then(nada, nada);

            return fila;
        }

        function gravarTexto() {
            if (!pronto || !gravacaoLigada) {
                return Promise.resolve();
            }

            var aba = idDaAba();

            if (!aba) {
                return Promise.resolve();
            }

            var registro = retratar(aba);

            return enfileirar(function () {
                return gravarRegistro(registro).then(function () {
                    gravarEm('localStorage', CHAVE_MARCADOR, '1');
                });
            });
        }

        function agendarTexto() {
            if (temporizadorTexto !== null) {
                clearTimeout(temporizadorTexto);
            }

            temporizadorTexto = setTimeout(function () {
                temporizadorTexto = null;
                gravarTexto();
            }, ESPERA_TEXTO_MS);
        }

        function descarregarTexto() {
            if (temporizadorTexto !== null) {
                clearTimeout(temporizadorTexto);
                temporizadorTexto = null;
            }

            return gravarTexto();
        }

        function lerBytes(arquivo) {
            return new Promise(function (resolver, rejeitar) {
                var leitor = new FileReader();

                leitor.onload = function () {
                    resolver(leitor.result);
                };

                leitor.onerror = function () {
                    rejeitar(leitor.error || new Error('leitura'));
                };

                leitor.readAsArrayBuffer(arquivo);
            });
        }

        // A assinatura (nome, tamanho, data) e' gravada de forma sincrona
        // no momento da escolha. Na volta, so' se recoloca o arquivo cuja
        // assinatura confere: uma gravacao que nao terminou, ou um arquivo
        // antigo que sobrou no banco, nunca volta ao campo.
        function tratarArquivo(campo) {
            if (!pronto || !gravacaoLigada) {
                return;
            }

            var aba = idDaAba();

            if (!aba) {
                return;
            }

            var metodo = metodoDoCampo(campo);
            var nomeCampo = campo.name;
            var chave = chaveArquivo(aba, evento, metodo, nomeCampo);
            var chaveDaAssinatura = chaveAssinatura(evento, metodo, nomeCampo);
            var assinaturas = lerAssinaturas();
            var arquivo = campo.files && campo.files.length > 0 ? campo.files[0] : null;

            if (arquivo === null) {
                delete assinaturas[chaveDaAssinatura];
                gravarAssinaturas(assinaturas);
                enfileirar(function () {
                    return apagarChaves([chave]);
                });

                return;
            }

            assinaturas[chaveDaAssinatura] = { nome: arquivo.name, tamanho: arquivo.size, modificado: arquivo.lastModified };

            if (!gravarAssinaturas(assinaturas)) {
                return;
            }

            // O registro de texto vai antes: arquivo sem ele e' tratado como
            // sobra pela limpeza.
            arquivosEmCurso++;
            gravarTexto();
            enfileirar(function () {
                return lerBytes(arquivo).then(function (dados) {
                    return gravarRegistro({
                        chave: chave,
                        aba: aba,
                        evento: evento,
                        metodo: metodo,
                        campo: nomeCampo,
                        nome: arquivo.name,
                        tipo: arquivo.type || '',
                        tamanho: arquivo.size,
                        modificado: arquivo.lastModified,
                        dados: dados
                    });
                }).then(function () {
                    gravarEm('localStorage', CHAVE_MARCADOR, '1');
                }, function () {
                    return apagarChaves([chave]).then(nada, nada);
                }).then(function () {
                    arquivosEmCurso--;
                });
            });
        }

        // O arquivo e' reconstruido em memoria a partir dos bytes guardados
        // e conferido depois de atribuido. O "change" faz a conferencia de
        // tamanho e extensao de trabalho-formulario.js rodar como numa
        // escolha manual (ela pode zerar o campo, e entao o arquivo conta
        // como nao recolocado).
        function recolocar(campo, registro, assinatura) {
            try {
                if (registro.nome !== assinatura.nome || registro.tamanho !== assinatura.tamanho || registro.modificado !== assinatura.modificado) {
                    return false;
                }

                if (!registro.dados || registro.dados.byteLength !== registro.tamanho) {
                    return false;
                }

                var transferencia = new DataTransfer();

                transferencia.items.add(new File([registro.dados], registro.nome, { type: registro.tipo || '', lastModified: registro.modificado }));
                campo.files = transferencia.files;

                if (!campo.files || campo.files.length !== 1 || campo.files[0].size !== registro.tamanho) {
                    campo.value = '';

                    return false;
                }

                disparar(campo, 'change');

                return !!(campo.files && campo.files.length === 1);
            } catch (erro) {
                try {
                    campo.value = '';
                } catch (outroErro) {
                    // O campo fica como estava.
                }

                return false;
            }
        }

        // Passo 4 da restauracao, sempre por ultimo: so' nos campos
        // habilitados da forma de envio ativa (os nomes dos campos se repetem
        // entre as secoes). Devolve, por campo, o que voltou e o que faltou.
        function reporArquivos() {
            var aba = lerDe('sessionStorage', CHAVE_ABA);
            var assinaturas = lerAssinaturas();
            var relatorio = [];
            var cadeia = Promise.resolve();

            camposDeArquivoAtivos().forEach(function (campo) {
                var metodo = metodoDoCampo(campo);
                var nomeCampo = campo.name;
                var chaveDaAssinatura = chaveAssinatura(evento, metodo, nomeCampo);
                var assinatura = assinaturas[chaveDaAssinatura];
                var chave = chaveArquivo(aba, evento, metodo, nomeCampo);

                if (!assinatura) {
                    return;
                }

                // Arquivo que o servidor acabou de recusar nao volta, e sai
                // do rascunho.
                if (modo === 'erro' && nomeCampo === campoErro) {
                    delete assinaturas[chaveDaAssinatura];
                    gravarAssinaturas(assinaturas);
                    cadeia = cadeia.then(function () {
                        return apagarChaves([chave]);
                    }).then(nada, nada);

                    return;
                }

                cadeia = cadeia.then(function () {
                    if (campo.files && campo.files.length > 0) {
                        return null;
                    }

                    return lerRegistro(chave).then(function (registro) {
                        relatorio.push({
                            campo: nomeCampo,
                            nome: assinatura.nome,
                            voltou: registro !== null && recolocar(campo, registro, assinatura)
                        });
                    }, function () {
                        relatorio.push({ campo: nomeCampo, nome: assinatura.nome, voltou: false });
                    });
                });
            });

            return cadeia.then(function () {
                return relatorio;
            });
        }

        // Passos 1 a 3 da restauracao, nesta ordem: forma de envio (com
        // "change", para trabalho-formulario.js mostrar a secao certa e
        // desabilitar as demais), coautores (pelo botao existente) e, por
        // fim, textos, listas e declaracoes. O rascunho so' prevalece em
        // campo que a pessoa nao mexeu desde a carga. Devolve se algo mudou.
        function reporTextos(registro) {
            var mudou = false;

            if (seletorMetodo && seletorMetodo.tagName === 'SELECT' && registro.metodo
                && seletorMetodo.value !== registro.metodo && temOpcao(seletorMetodo, registro.metodo)) {
                seletorMetodo.value = registro.metodo;
                disparar(seletorMetodo, 'change');
                mudou = true;
            }

            var coautores = registro.coautores || [];
            var listaCoautores = document.getElementById('trabalho-coautores-lista');
            var botaoAdicionar = document.getElementById('trabalho-coautor-adicionar');

            if (listaCoautores && botaoAdicionar && coautores.length > 0) {
                // O clique no botao foca o primeiro campo do bloco novo e
                // rola a tela: o foco e a rolagem de antes sao repostos.
                var focoAntes = document.activeElement;
                var rolagemX = window.pageXOffset;
                var rolagemY = window.pageYOffset;
                var blocos = listaCoautores.querySelectorAll('.trabalho-coautor-item');
                var tentativas = 0;

                while (blocos.length < coautores.length && !botaoAdicionar.disabled && tentativas < 50) {
                    botaoAdicionar.click();
                    blocos = listaCoautores.querySelectorAll('.trabalho-coautor-item');
                    tentativas++;
                    mudou = true;
                }

                Array.prototype.forEach.call(blocos, function (bloco, posicao) {
                    var valores = coautores[posicao];

                    if (!valores) {
                        return;
                    }

                    Array.prototype.forEach.call(bloco.querySelectorAll('input[name]'), function (campo) {
                        var valor = valores[campo.name];

                        if (typeof valor !== 'string' || valor === '' || campo.value !== campo.defaultValue || campo.value === valor) {
                            return;
                        }

                        campo.value = valor;
                        mudou = true;

                        if (campo.classList.contains('campo-cpf-validar')) {
                            disparar(campo, 'focusout');
                        }
                    });
                });

                if (document.activeElement && document.activeElement !== focoAntes && document.activeElement.blur) {
                    document.activeElement.blur();
                }

                if (focoAntes && focoAntes !== document.body && focoAntes.focus) {
                    focoAntes.focus({ preventScroll: true });
                }

                window.scrollTo(rolagemX, rolagemY);
            }

            var campos = registro.campos || {};

            // Sem "input" sintetico: o valor foi guardado ja' formatado, e o
            // evento moveria o cursor (mascaras) e limparia destaque de erro.
            // No CPF, "focusout" faz o aviso "CPF invalido" aparecer como
            // apareceria a quem digitou.
            Array.prototype.forEach.call(formulario.elements, function (campo) {
                if (!campoDeTexto(campo) || !Object.prototype.hasOwnProperty.call(campos, campo.name)) {
                    return;
                }

                var valor = campos[campo.name];

                if (typeof valor !== 'string' || valor === '' || campo.value === valor) {
                    return;
                }

                if (campo.tagName === 'SELECT') {
                    if (campo.value === valorPadraoDaLista(campo) && temOpcao(campo, valor)) {
                        campo.value = valor;
                        mudou = true;
                    }

                    return;
                }

                if (campo.value !== campo.defaultValue) {
                    return;
                }

                campo.value = valor;
                mudou = true;

                if (campo.classList.contains('campo-cpf-validar')) {
                    disparar(campo, 'focusout');
                }
            });

            var termos = registro.termos || [];

            Array.prototype.forEach.call(formulario.querySelectorAll('input[type="checkbox"][name="termos_aceitos[]"]'), function (caixa) {
                if (termos.indexOf(caixa.value) !== -1 && !caixa.checked) {
                    caixa.checked = true;
                    mudou = true;
                }
            });

            return mudou;
        }

        function mostrarRecuperado(relatorio) {
            var lista = document.getElementById('trabalho-rascunho-arquivos');

            if (lista) {
                lista.textContent = '';

                relatorio.forEach(function (item) {
                    var linha = document.createElement('li');
                    var rotulo = lista.getAttribute('data-rotulo-' + item.campo) || item.campo;

                    linha.textContent = item.voltou
                        ? rotulo + ': ' + item.nome + ' (' + lista.getAttribute('data-texto-voltou') + ')'
                        : rotulo + ': ' + lista.getAttribute('data-texto-faltou');
                    lista.appendChild(linha);
                });

                lista.hidden = relatorio.length === 0;
            }

            mostrar('trabalho-rascunho-recuperado');
        }

        // A frase fixa da caixa de erro ("escolha os arquivos outra vez") so'
        // e' trocada quando TODOS os campos de arquivo da forma de envio
        // ativa ficaram com arquivo.
        function ajustarFraseDeErro(relatorio) {
            var trecho = document.getElementById('trabalho-erro-arquivos');
            var ativos = camposDeArquivoAtivos();
            var algumVoltou = relatorio.some(function (item) {
                return item.voltou;
            });
            var todosCheios = ativos.length > 0 && ativos.every(function (campo) {
                return campo.files && campo.files.length === 1;
            });

            if (trecho && algumVoltou && todosCheios) {
                trecho.textContent = trecho.getAttribute('data-texto-recuperado');
            }
        }

        // Rascunho desta aba para este evento, ja' conferido: fora da
        // validade sai; de outra conta sai; de uma conta, visto por
        // visitante, fica guardado e intocado ate' a pessoa entrar.
        function carregar() {
            var aba = lerDe('sessionStorage', CHAVE_ABA);

            if (!aba) {
                return Promise.resolve(null);
            }

            return lerRegistro(chaveTexto(aba, evento)).then(function (registro) {
                if (registro === null) {
                    limparAssinaturasDoEvento(evento);

                    return null;
                }

                var vencido = registro.gravado_em + VALIDADE_MS < Date.now();
                var deOutraConta = registro.conta && contaAtual && String(registro.conta) !== contaAtual;

                if (vencido || deOutraConta) {
                    return apagarRascunhoDaAba(evento).then(function () {
                        return null;
                    });
                }

                if (registro.conta && !contaAtual) {
                    gravacaoLigada = false;
                    mostrar('trabalho-rascunho-de-conta');

                    return null;
                }

                // Rascunho de visitante e' adotado pela primeira conta que
                // entrar: a marca e' gravada assim que a restauracao termina,
                // para uma segunda conta na mesma aba nao o receber.
                adotar = contaAtual !== '' && !registro.conta;

                return registro;
            });
        }

        function restaurar(registro) {
            if (registro === null) {
                return Promise.resolve();
            }

            restaurando = true;

            var mudou = false;

            try {
                if (modo !== 'erro') {
                    mudou = reporTextos(registro);
                }
            } catch (erro) {
                // O que ja' foi reposto fica; os arquivos ainda sao tentados.
            }

            return reporArquivos().then(function (relatorio) {
                if (modo === 'erro') {
                    ajustarFraseDeErro(relatorio);
                } else if (mudou || relatorio.length > 0) {
                    mostrarRecuperado(relatorio);
                }
            });
        }

        function ligarGravacao() {
            formulario.addEventListener('input', function () {
                if (!restaurando) {
                    agendarTexto();
                }
            });

            // O arquivo e' lido depois dos demais tratadores do "change":
            // o de trabalho-formulario.js zera o campo acima do limite.
            formulario.addEventListener('change', function (eventoDom) {
                if (restaurando) {
                    return;
                }

                var campo = eventoDom.target;

                if (campo && campo.tagName === 'INPUT' && campo.type === 'file') {
                    setTimeout(function () {
                        try {
                            tratarArquivo(campo);
                        } catch (erro) {
                            // O envio nao depende do rascunho.
                        }
                    }, 0);

                    return;
                }

                agendarTexto();
            });

            // Adicionar e remover coautor nao geram "input".
            formulario.addEventListener('click', function (eventoDom) {
                var alvo = eventoDom.target;

                if (restaurando || !alvo || !alvo.classList) {
                    return;
                }

                if (alvo.classList.contains('trabalho-coautor-remover') || alvo.id === 'trabalho-coautor-adicionar') {
                    agendarTexto();
                }
            });

            // O envio nao e' interceptado: so' se grava o sinal "enviando"
            // e se descarrega o texto pendente, sem aguardar.
            formulario.addEventListener('submit', function () {
                try {
                    gravarEm('sessionStorage', CHAVE_ENVIANDO, evento);
                    descarregarTexto();
                } catch (erro) {
                    // O envio segue.
                }
            });
        }

        // "Entrar para enviar": o atalho funciona sozinho. Aqui so' se
        // espera a gravacao pendente antes de sair, com teto: 3 segundos
        // para texto, 60 quando ha' arquivo sendo guardado. O temporizador
        // e' armado antes de segurar o clique, e um segundo clique sai na
        // hora.
        function ligarEntrar() {
            if (!botaoEntrar) {
                return;
            }

            var esperando = false;

            botaoEntrar.addEventListener('click', function (eventoDom) {
                if (esperando || !pronto || !gravacaoLigada) {
                    return;
                }

                if (eventoDom.button !== 0 || eventoDom.metaKey || eventoDom.ctrlKey || eventoDom.shiftKey || eventoDom.altKey) {
                    return;
                }

                var destino = botaoEntrar.href;
                var saiu = false;
                var sair = function () {
                    if (!saiu) {
                        saiu = true;
                        window.location.href = destino;
                    }
                };

                setTimeout(sair, arquivosEmCurso > 0 ? TETO_ENTRAR_ARQUIVO_MS : TETO_ENTRAR_TEXTO_MS);
                esperando = true;
                eventoDom.preventDefault();

                if (arquivosEmCurso > 0 && botaoEntrar.getAttribute('data-texto-guardando')) {
                    botaoEntrar.textContent = botaoEntrar.getAttribute('data-texto-guardando');
                }

                descarregarTexto();
                fila.then(sair, sair);
            });
        }

        function ligarDescartar() {
            var botao = document.getElementById('trabalho-rascunho-descartar');

            if (!botao) {
                return;
            }

            botao.addEventListener('click', function () {
                var recarregar = function () {
                    window.location.reload();
                };

                gravacaoLigada = false;

                if (temporizadorTexto !== null) {
                    clearTimeout(temporizadorTexto);
                    temporizadorTexto = null;
                }

                comTeto(fila.then(function () {
                    return apagarRascunhoDaAba(evento);
                }), TETO_BANCO_MS).then(recarregar, recarregar);
            });
        }

        // Teste real: gravar e ler um bloco pequeno e montar um arquivo num
        // DataTransfer. So' a existencia dos objetos nao basta (navegacao
        // privada costuma te-los e recusar a gravacao).
        function testarCapacidade() {
            try {
                if (!window.indexedDB || typeof window.DataTransfer !== 'function' || typeof window.File !== 'function' || typeof window.FileReader !== 'function') {
                    return Promise.reject(new Error('capacidade'));
                }

                var transferencia = new DataTransfer();

                transferencia.items.add(new File(['x'], 'teste.txt', { type: 'text/plain' }));

                if (transferencia.files.length !== 1) {
                    return Promise.reject(new Error('capacidade'));
                }

                if (!gravarEm('sessionStorage', CHAVE_ABA + '_teste', '1') || !gravarEm('localStorage', CHAVE_MARCADOR + '_teste', '1')) {
                    return Promise.reject(new Error('capacidade'));
                }

                removerDe('sessionStorage', CHAVE_ABA + '_teste');
                removerDe('localStorage', CHAVE_MARCADOR + '_teste');
            } catch (erro) {
                return Promise.reject(erro);
            }

            var chave = 'teste|' + Date.now() + '|' + aleatorio();

            return gravarRegistro({ chave: chave, dados: new Uint8Array([1, 2, 3]).buffer }).then(function () {
                return lerRegistro(chave);
            }).then(function (registro) {
                if (registro === null || !registro.dados || registro.dados.byteLength !== 3) {
                    throw new Error('capacidade');
                }

                return apagarChaves([chave]);
            });
        }

        function semCapacidade() {
            var apoio = document.getElementById('trabalho-entrar-apoio');

            // So' o visitante tem o aviso na tela; para quem ja' entrou, a
            // falta do rascunho e' o comportamento de sempre.
            mostrar('trabalho-rascunho-sem-capacidade');

            if (apoio && document.getElementById('trabalho-rascunho-sem-capacidade')) {
                apoio.hidden = true;
            }
        }

        comTeto(testarCapacidade(), TETO_BANCO_MS * 2).then(function () {
            var liberar = function () {
                restaurando = false;
                pronto = true;

                if (adotar) {
                    gravarTexto();
                }
            };

            // Nenhuma gravacao comeca antes de a restauracao terminar:
            // a primeira tecla sobrescreveria o rascunho que ainda nao voltou.
            ligarGravacao();
            ligarEntrar();
            ligarDescartar();

            return comTeto(carregar().then(restaurar), TETO_RESTAURACAO_MS).then(liberar, liberar);
        }, semCapacidade).then(nada, nada);
    }

    var inicio;

    try {
        inicio = parteGlobal();
    } catch (erro) {
        inicio = Promise.resolve();
    }

    // A limpeza global termina antes de a parte do formulario comecar.
    inicio.then(nada, nada).then(function () {
        try {
            parteFormulario();
        } catch (erro) {
            // A tela fica como o servidor a montou.
        }
    });
})();
