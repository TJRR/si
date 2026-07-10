(function () {
    var marcarTodos = document.getElementById('marcar-todos');

    if (!marcarTodos) {
        return;
    }

    marcarTodos.addEventListener('change', function () {
        var linhas = document.querySelectorAll('.marcar-linha');

        for (var i = 0; i < linhas.length; i++) {
            linhas[i].checked = marcarTodos.checked;
        }
    });
})();
