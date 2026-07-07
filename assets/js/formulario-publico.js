(function () {
    var template = document.getElementById('template-grupo-participante-linha');

    if (!template) {
        return;
    }

    document.querySelectorAll('.grupo-participantes').forEach(function (grupo) {
        var campoId = grupo.getAttribute('data-campo-id');
        var maximo = parseInt(grupo.getAttribute('data-maximo'), 10) || 10;
        var container = grupo.querySelector('.grupo-participantes-linhas');
        var botaoAdicionar = grupo.querySelector('.grupo-participantes-adicionar');

        function contarLinhas() {
            return container.querySelectorAll('.grupo-participantes-linha').length;
        }

        function ligarRemover(linha) {
            var botaoRemover = linha.querySelector('.grupo-participantes-remover');
            botaoRemover.addEventListener('click', function () {
                if (contarLinhas() > 1) {
                    linha.remove();
                }
            });
        }

        container.querySelectorAll('.grupo-participantes-linha').forEach(ligarRemover);

        botaoAdicionar.addEventListener('click', function () {
            if (contarLinhas() >= maximo) {
                return;
            }

            var proximoIndice = parseInt(grupo.getAttribute('data-proximo-indice'), 10) || 0;

            var html = template.innerHTML
                .split('__ID__').join(campoId)
                .split('__INDICE__').join(proximoIndice);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            var novaLinha = wrapper.firstElementChild;

            container.appendChild(novaLinha);
            ligarRemover(novaLinha);

            grupo.setAttribute('data-proximo-indice', proximoIndice + 1);
        });
    });
})();
