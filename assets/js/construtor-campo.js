(function () {
    var selectTipo = document.getElementById('campo-tipo');
    var blocoGrupoParticipantes = document.getElementById('config-grupo-participantes');

    if (!selectTipo || !blocoGrupoParticipantes) {
        return;
    }

    function atualizar() {
        blocoGrupoParticipantes.style.display = selectTipo.value === 'grupo_participantes' ? 'block' : 'none';
    }

    selectTipo.addEventListener('change', atualizar);
    atualizar();
})();
