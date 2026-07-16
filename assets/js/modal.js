/**
 * Fase 17 (Bug 5 / Melhoria 1): modal generico reutilizavel - primeira vez
 * que esse padrao entra no projeto. Funcoes globais (chamadas de atributos
 * onclick="" e de outros scripts, como assets/js/avaliacao-notar.js).
 */
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
