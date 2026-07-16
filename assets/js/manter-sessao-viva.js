(function () {
    'use strict';

    var script = document.currentScript;
    var url = script.getAttribute('data-url');
    var intervaloMs = 3 * 60 * 1000;

    if (!url) {
        return;
    }

    setInterval(function () {
        fetch(url, { credentials: 'same-origin' }).catch(function () {});
    }, intervaloMs);
})();
