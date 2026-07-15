(function () {
    'use strict';

    var arvore = document.getElementById('arvore-admin');
    var abasNav = document.getElementById('abas-admin');
    var conteudo = document.getElementById('conteudo-admin');

    if (!arvore || !conteudo) {
        return;
    }

    var rotulosVazio = {
        trilhas: 'Nenhuma trilha ainda',
        etapas: 'Nenhuma etapa ainda'
    };

    var noAtivo = lerNoAtivoInicial();

    function url(rota) {
        return window.SI_BASE_PATH + '/index.php?r=' + rota;
    }

    function lerNoAtivoInicial() {
        var li = arvore.querySelector('.arvore-no.ativo');

        if (!li) {
            return null;
        }

        return { tipo: li.dataset.tipo, id: li.dataset.id };
    }

    function marcarNoAtivoNaArvore(tipo, id) {
        var atual = arvore.querySelector('.arvore-no.ativo');

        if (atual) {
            atual.classList.remove('ativo');
        }

        if (tipo === null) {
            return;
        }

        var alvo = arvore.querySelector('.arvore-no[data-tipo="' + tipo + '"][data-id="' + id + '"]');

        if (alvo) {
            alvo.classList.add('ativo');
        }
    }

    function construirLiNo(no) {
        var li = document.createElement('li');
        li.className = 'arvore-no';
        li.dataset.tipo = no.tipo;
        li.dataset.id = no.id;

        var linha = document.createElement('div');
        linha.className = 'arvore-linha';

        if (no.folha) {
            var espaco = document.createElement('span');
            espaco.className = 'arvore-spacer';
            linha.appendChild(espaco);
        } else {
            var caret = document.createElement('button');
            caret.type = 'button';
            caret.className = 'arvore-caret';
            caret.setAttribute('aria-label', 'Expandir/recolher');
            caret.textContent = '▸';
            linha.appendChild(caret);
        }

        var link = document.createElement('a');
        link.className = 'arvore-rotulo';
        link.href = url(no.url);
        link.textContent = no.rotulo;
        linha.appendChild(link);

        li.appendChild(linha);

        if (!no.folha) {
            var filhos = document.createElement('ul');
            filhos.className = 'arvore-filhos arvore-filhos-fechado';
            li.appendChild(filhos);
        }

        return li;
    }

    function expandirNo(li) {
        var caret = li.querySelector(':scope > .arvore-linha > .arvore-caret');
        var listaFilhos = li.querySelector(':scope > .arvore-filhos');

        if (!listaFilhos) {
            return;
        }

        if (!listaFilhos.classList.contains('arvore-filhos-fechado')) {
            listaFilhos.classList.add('arvore-filhos-fechado');
            if (caret) { caret.classList.remove('aberto'); }
            return;
        }

        if (listaFilhos.dataset.carregado === '1') {
            listaFilhos.classList.remove('arvore-filhos-fechado');
            if (caret) { caret.classList.add('aberto'); }
            return;
        }

        if (caret) { caret.classList.add('carregando'); }

        fetch(url('navegacao/filhos/' + li.dataset.tipo + '/' + li.dataset.id), {
            headers: { 'X-Requisicao': 'parcial' }
        })
            .then(function (resposta) { return resposta.json(); })
            .then(function (filhos) {
                listaFilhos.innerHTML = '';

                if (filhos.length === 0) {
                    var vazio = document.createElement('li');
                    vazio.className = 'arvore-vazio';
                    vazio.textContent = rotulosVazio[li.dataset.tipo] || 'Nenhum item ainda';
                    listaFilhos.appendChild(vazio);
                } else {
                    filhos.forEach(function (no) {
                        listaFilhos.appendChild(construirLiNo(no));
                    });
                }

                listaFilhos.dataset.carregado = '1';
                listaFilhos.classList.remove('arvore-filhos-fechado');
                if (caret) {
                    caret.classList.remove('carregando');
                    caret.classList.add('aberto');
                }
            });
    }

    function sincronizarFieldsetAvaliacao() {
        var select = document.getElementById('campo-mecanismo-avaliacao');
        var fieldset = document.getElementById('fieldset-avaliacao-por-avaliadores');

        if (!select || !fieldset) {
            return;
        }

        fieldset.style.display = select.value === 'avaliadores' ? '' : 'none';
    }

    function sincronizarAbasAvaliacao() {
        if (!abasNav) {
            return;
        }

        var select = document.getElementById('campo-mecanismo-avaliacao');
        var mecanismoAtual = select ? select.value : abasNav.dataset.mecanismoAvaliacaoEtapa;

        abasNav.querySelectorAll('.aba-secundaria[data-somente-avaliadores="1"]').forEach(function (link) {
            link.style.display = mecanismoAtual === 'avaliadores' ? '' : 'none';
        });
    }

    function renderizarAbas(abas, mecanismoAvaliacaoEtapa, urlAtual) {
        if (!abasNav) {
            return;
        }

        abasNav.innerHTML = '';
        abasNav.dataset.mecanismoAvaliacaoEtapa = mecanismoAvaliacaoEtapa || '';

        if (!abas || abas.length === 0) {
            abasNav.style.display = 'none';
            return;
        }

        abasNav.style.display = '';

        abas.forEach(function (aba) {
            var link = document.createElement('a');
            link.className = 'aba-secundaria' + (aba.ativa ? ' active' : '');
            link.href = url(aba.url);
            link.textContent = aba.rotulo;
            link.dataset.somenteAvaliadores = aba.somenteAvaliadores ? '1' : '0';
            abasNav.appendChild(link);
        });
    }

    function navegar(rota, opcoes) {
        opcoes = opcoes || {};

        fetch(url(rota), { headers: { 'X-Requisicao': 'parcial' } })
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                conteudo.innerHTML = dados.conteudo;
                renderizarAbas(dados.abas, dados.mecanismoAvaliacaoEtapa, rota);
                sincronizarFieldsetAvaliacao();
                sincronizarAbasAvaliacao();

                if (dados.titulo) {
                    document.title = dados.titulo;
                }

                if (dados.flash) {
                    var aviso = document.createElement('p');
                    aviso.style.color = 'green';
                    aviso.textContent = dados.flash;
                    conteudo.insertBefore(aviso, conteudo.firstChild);
                }

                if (!opcoes.semHistorico) {
                    history.pushState({ tipo: noAtivo ? noAtivo.tipo : null, id: noAtivo ? noAtivo.id : null }, '', url(rota));
                }

                window.scrollTo(0, 0);
            });
    }

    arvore.addEventListener('click', function (evento) {
        var caret = evento.target.closest('.arvore-caret');

        if (caret) {
            expandirNo(caret.closest('.arvore-no'));
            return;
        }

        var link = evento.target.closest('.arvore-rotulo');

        if (!link) {
            return;
        }

        var li = link.closest('.arvore-no');

        if (!li) {
            return;
        }

        evento.preventDefault();

        noAtivo = { tipo: li.dataset.tipo, id: li.dataset.id };
        marcarNoAtivoNaArvore(noAtivo.tipo, noAtivo.id);

        var rota = link.getAttribute('href').replace(url(''), '');
        navegar(rota);

        if (!li.querySelector('.arvore-caret')) {
            return;
        }

        var listaFilhos = li.querySelector(':scope > .arvore-filhos');
        if (listaFilhos && listaFilhos.classList.contains('arvore-filhos-fechado')) {
            expandirNo(li);
        }
    });

    if (abasNav) {
        abasNav.addEventListener('click', function (evento) {
            var link = evento.target.closest('.aba-secundaria');

            if (!link) {
                return;
            }

            evento.preventDefault();
            var rota = link.getAttribute('href').replace(url(''), '');
            navegar(rota);
        });
    }

    document.addEventListener('change', function (evento) {
        if (evento.target.id === 'campo-mecanismo-avaliacao') {
            sincronizarFieldsetAvaliacao();
            sincronizarAbasAvaliacao();
            return;
        }

        if (evento.target.id !== 'marcar-todos') {
            return;
        }

        document.querySelectorAll('.marcar-linha').forEach(function (linha) {
            linha.checked = evento.target.checked;
        });
    });

    sincronizarFieldsetAvaliacao();
    sincronizarAbasAvaliacao();

    window.addEventListener('popstate', function (evento) {
        var rota = location.href.replace(url(''), '');
        var estado = evento.state;

        if (estado && estado.tipo) {
            noAtivo = { tipo: estado.tipo, id: estado.id };
            marcarNoAtivoNaArvore(noAtivo.tipo, noAtivo.id);
        }

        navegar(rota, { semHistorico: true });
    });
})();
