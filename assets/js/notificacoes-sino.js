(function () {
    var botao = document.getElementById('notificacoes-sino-botao');
    var painel = document.getElementById('notificacoes-sino-painel');

    if (!botao || !painel) {
        return;
    }

    function fechar() {
        painel.classList.remove('aberto');
        botao.setAttribute('aria-expanded', 'false');
    }

    botao.addEventListener('click', function (evento) {
        evento.stopPropagation();
        var abrindo = !painel.classList.contains('aberto');
        painel.classList.toggle('aberto', abrindo);
        botao.setAttribute('aria-expanded', abrindo ? 'true' : 'false');
    });

    painel.addEventListener('click', function (evento) {
        evento.stopPropagation();
    });

    document.addEventListener('click', fechar);
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            fechar();
        }
    });
})();
