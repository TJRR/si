/**
 * Fase 17 (Bug 5 / Melhoria 1): modal generico reutilizavel - primeira vez
 * que esse padrao entra no projeto. Funcoes globais (chamadas de atributos
 * onclick="" e de outros scripts, como assets/js/avaliacao-notar.js).
 */

/**
 * Fase 33: sequencia do modal navegavel - lista de URLs e posicao atual.
 * null quando o modal aberto nao faz parte de nenhuma sequencia (ajuda
 * contextual, popup de submissao, etc.), e a barra de navegacao fica oculta.
 * Declarada aqui em cima porque fecharModal() ja' a limpa.
 */
var sequenciaModal = null;

function abrirModal(titulo, htmlConteudo) {
    var overlay = document.getElementById('modal-generico');

    if (!overlay) {
        return;
    }

    document.getElementById('modal-titulo').textContent = titulo;
    document.getElementById('modal-conteudo').innerHTML = htmlConteudo;
    overlay.hidden = false;
}

function fecharModal() {
    var overlay = document.getElementById('modal-generico');

    if (!overlay) {
        return;
    }

    overlay.hidden = true;
    document.getElementById('modal-conteudo').innerHTML = '';

    // Fase 33: sem isso, reabrir por um caminho que nao monta sequencia
    // (ajuda contextual, popup de submissao) herdaria a barra da anterior.
    sequenciaModal = null;
    atualizarNavegacaoModal();
}

/**
 * Fase 33: abre o item e monta a sequencia a partir da PROPRIA TABELA em que
 * o link foi clicado (elemento.closest('table')), nunca de uma consulta nova
 * ao servidor - assim a ordem, os filtros e o recorte da lista que a pessoa
 * esta' vendo sao exatamente os mesmos da navegacao, sem nenhuma rota ou
 * parametro extra. Cada tabela do painel e' uma sequencia independente (o
 * painel tem tabelas separadas de "todas" e "escaladas pra mim").
 *
 * Tabela com um item so' (ou link solto fora de tabela) cai no modal simples,
 * sem barra de navegacao - nao ha' pra onde ir.
 */
function abrirModalNavegavel(titulo, elemento) {
    var tabela = elemento.closest ? elemento.closest('table') : null;
    var links = tabela ? tabela.querySelectorAll('a[data-modal-nav]') : null;

    if (!links || links.length < 2) {
        sequenciaModal = null;
        abrirModalUrl(titulo, elemento.href);
        return;
    }

    sequenciaModal = {
        titulo: titulo,
        urls: Array.prototype.map.call(links, function (link) {
            return link.href;
        }),
        indice: Array.prototype.indexOf.call(links, elemento)
    };

    abrirItemSequenciaModal();
}

function navegarModal(passo) {
    if (sequenciaModal === null) {
        return;
    }

    var novoIndice = sequenciaModal.indice + passo;

    if (novoIndice < 0 || novoIndice >= sequenciaModal.urls.length) {
        return;
    }

    sequenciaModal.indice = novoIndice;
    abrirItemSequenciaModal();
}

function abrirItemSequenciaModal() {
    atualizarNavegacaoModal();
    abrirModalUrl(sequenciaModal.titulo, sequenciaModal.urls[sequenciaModal.indice]);
}

/**
 * Mostra a barra e liga/desliga os botoes nas pontas da sequencia. O shell do
 * modal so' existe no painel (ver layout.php), entao tudo aqui e' tolerante a
 * elemento ausente.
 */
function atualizarNavegacaoModal() {
    var barra = document.getElementById('modal-navegacao');

    if (!barra) {
        return;
    }

    if (sequenciaModal === null) {
        barra.hidden = true;
        return;
    }

    var total = sequenciaModal.urls.length;
    var posicao = sequenciaModal.indice + 1;

    document.getElementById('modal-posicao').textContent = posicao + ' de ' + total;
    document.getElementById('modal-anterior').disabled = sequenciaModal.indice === 0;
    document.getElementById('modal-proximo').disabled = sequenciaModal.indice === total - 1;
    barra.hidden = false;
}

/**
 * Busca o conteudo via fetch com o cabecalho X-Requisicao: parcial - mesmo
 * mecanismo ja usado por assets/js/navegacao-arvore.js (Controller::renderizar()
 * devolve JSON {conteudo: "..."} quando esse cabecalho esta presente, sem
 * precisar de nenhuma rota nova so' para popup).
 */
function abrirModalUrl(titulo, url) {
    abrirModal(titulo, '<p>Carregando...</p>');

    fetch(url, { headers: { 'X-Requisicao': 'parcial' }, credentials: 'same-origin' })
        .then(function (resposta) {
            return resposta.json();
        })
        .then(function (dados) {
            abrirModal(titulo, dados.conteudo);
        })
        .catch(function () {
            abrirModal(titulo, '<p>Não foi possível carregar o conteúdo.</p>');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('modal-generico');

    if (!overlay) {
        return;
    }

    overlay.addEventListener('click', function (evento) {
        if (evento.target === overlay) {
            fecharModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !overlay.hidden) {
            fecharModal();
        }
    });
});
