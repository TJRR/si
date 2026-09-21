/**
 * Fase 48 (correcao pos-teste de fumaca): campo de busca de usuario ja
 * cadastrado, por nome ou e-mail, com sugestoes em tempo real - substitui
 * digitar o e-mail de memoria. Reaproveitavel por qualquer tela que precise
 * desse padrao: basta um container com data-busca-usuario + data-endpoint,
 * um campo de texto data-busca-usuario-input, uma lista
 * data-busca-usuario-resultados e um campo oculto data-busca-usuario-id
 * (e' este ultimo que o formulario de fato envia).
 *
 * Reage tanto ao carregamento direto da pagina quanto a navegacao pela
 * arvore lateral (troca de #conteudo-admin via AJAX, ver
 * navegacao-arvore.js) - essa segunda so' substitui o HTML, nunca reexecuta
 * os <script src="..."> do layout, entao este arquivo escuta o evento
 * customizado 'conteudo-admin-atualizado' para inicializar o componente de
 * novo quando ele aparecer depois de uma navegacao (achado real: sem isso,
 * a busca simplesmente nao respondia a nenhum clique/digitacao apos
 * navegar pela arvore, so' funcionava em F5 direto na URL).
 */
(function () {
    'use strict';

    function debounce(fn, esperaMs) {
        var temporizador = null;

        return function () {
            var args = arguments;
            clearTimeout(temporizador);
            temporizador = setTimeout(function () {
                fn.apply(null, args);
            }, esperaMs);
        };
    }

    function inicializar(container) {
        if (container.dataset.buscaUsuarioInicializado === '1') {
            return;
        }
        container.dataset.buscaUsuarioInicializado = '1';

        var campoTexto = container.querySelector('[data-busca-usuario-input]');
        var lista = container.querySelector('[data-busca-usuario-resultados]');
        var campoId = container.querySelector('[data-busca-usuario-id]');

        var buscar = debounce(function () {
            var termo = campoTexto.value.trim();
            campoId.value = '';

            if (termo.length < 2) {
                lista.hidden = true;
                lista.innerHTML = '';
                return;
            }

            // '&', nunca '?': container.dataset.endpoint ja' vem de url()
            // (app/helpers.php), que ja' devolve 'index.php?r=...' com '?'
            // proprio - um segundo '?' vira parte LITERAL do valor de 'r'
            // em vez de abrir a querystring, quebrando o parsing da rota
            // inteira (mesmo erro ja documentado no projeto, Fases 40/41 -
            // cometido de novo aqui, corrigido apos o usuario flagrar o 404).
            fetch(container.dataset.endpoint + '&q=' + encodeURIComponent(termo), { credentials: 'same-origin' })
                .then(function (resposta) { return resposta.json(); })
                .then(function (usuarios) {
                    lista.innerHTML = '';

                    if (!usuarios.length) {
                        lista.hidden = true;
                        return;
                    }

                    usuarios.forEach(function (usuario) {
                        var item = document.createElement('li');
                        item.textContent = usuario.nome + ' (' + usuario.email + ')';
                        item.addEventListener('click', function () {
                            campoId.value = usuario.id;
                            campoTexto.value = usuario.nome + ' (' + usuario.email + ')';
                            lista.hidden = true;
                            lista.innerHTML = '';
                            preencherCamposSeparados(container, usuario);
                            carregarPerfilExistente(container, usuario.id);
                        });
                        lista.appendChild(item);
                    });

                    lista.hidden = false;
                })
                .catch(function () {
                    lista.hidden = true;
                });
        }, 300);

        campoTexto.addEventListener('input', buscar);

        document.addEventListener('click', function (evento) {
            if (!container.contains(evento.target)) {
                lista.hidden = true;
            }
        });
    }

    /**
     * Fase 49: preenchimento aditivo, opcional - so' roda quando o
     * container declara data-preencher-nome/data-preencher-email (um
     * seletor CSS, ex.: "[name=nome]"), apontando para campos SEPARADOS de
     * nome/e-mail no mesmo <form>. Sem esses atributos, nada muda no
     * comportamento ja existente (Facilitador so' usa campoId/campoTexto
     * combinados, nunca precisou de campos separados).
     */
    function preencherCamposSeparados(container, usuario) {
        var form = container.closest('form');

        if (!form) {
            return;
        }

        var campoNome = container.dataset.preencherNome ? form.querySelector(container.dataset.preencherNome) : null;
        var campoEmail = container.dataset.preencherEmail ? form.querySelector(container.dataset.preencherEmail) : null;

        if (campoNome) {
            campoNome.value = usuario.nome;
        }

        if (campoEmail) {
            campoEmail.value = usuario.email;
        }
    }

    /**
     * Documento/cargo/categoria/orgao de origem/minicurriculo/foto sao
     * atributos da PESSOA, nunca da designacao especifica - se a mesma
     * pessoa ja tiver esses dados de uma vinculacao anterior (nesta
     * atividade ou em outra), preenche os campos automaticamente em vez de
     * exigir redigitar tudo de novo. Precisa de container.dataset.perfilEndpoint
     * (opcional - sem ele, este passo e' pulado) e campos [data-campo-perfil]
     * dentro do MESMO <form> do container de busca.
     */
    function carregarPerfilExistente(container, usuarioId) {
        if (!container.dataset.perfilEndpoint) {
            return;
        }

        var form = container.closest('form');

        if (!form) {
            return;
        }

        fetch(container.dataset.perfilEndpoint + '&usuario_id=' + encodeURIComponent(usuarioId), { credentials: 'same-origin' })
            .then(function (resposta) { return resposta.json(); })
            .then(function (perfil) {
                if (!perfil) {
                    return;
                }

                form.querySelectorAll('[data-campo-perfil]').forEach(function (campo) {
                    var chave = campo.dataset.campoPerfil;

                    if (perfil[chave] !== undefined && perfil[chave] !== null && perfil[chave] !== '') {
                        campo.value = perfil[chave];
                    }
                });

                var fotoAtual = form.querySelector('[data-foto-atual]');
                var houveDadoPreenchido = ['documento', 'cargo', 'categoria_profissional', 'orgao_origem', 'minicurriculo'].some(function (chave) {
                    return perfil[chave];
                });

                if (fotoAtual) {
                    fotoAtual.textContent = perfil.foto_path ? 'Já existe uma foto de perfil cadastrada; envie outra apenas para substituí-la.' : '';
                }

                var aviso = container.querySelector('[data-busca-usuario-aviso]');

                if (aviso) {
                    aviso.hidden = !houveDadoPreenchido;
                }
            })
            .catch(function () {
                // Silencioso: o Admin ainda pode preencher tudo manualmente.
            });
    }

    function inicializarTodos() {
        document.querySelectorAll('[data-busca-usuario]').forEach(inicializar);
    }

    inicializarTodos();
    document.addEventListener('conteudo-admin-atualizado', inicializarTodos);
})();
