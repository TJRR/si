(function () {
    var abas = document.querySelectorAll('.aba-criterio');

    abas.forEach(function (aba) {
        aba.addEventListener('click', function (evento) {
            evento.preventDefault();

            abas.forEach(function (outraAba) {
                outraAba.classList.remove('active');
                document.getElementById(outraAba.getAttribute('data-aba-criterio')).style.display = 'none';
            });

            aba.classList.add('active');
            document.getElementById(aba.getAttribute('data-aba-criterio')).style.display = '';
        });
    });
})();
