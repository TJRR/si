/**
 * Fase 43: componente de leitura de codigo (camera + digitacao manual),
 * reaproveitavel por qualquer tela que inclua o parcial
 * eventoApp/_leitor_codigo.php - basta um container com data-leitor-codigo
 * + data-endpoint. BarcodeDetector nativo so' existe em Chrome/Edge (desde
 * v83) - Firefox nunca implementou, Safari (desktop e iOS) mantem
 * desabilitado por padrao em toda versao. Por isso o campo de digitacao
 * manual do codigo de 6 caracteres fica SEMPRE visivel, e e' o UNICO
 * caminho nesses navegadores - decisao da Fase 43, sem lib JS de terceiros
 * nem decodificacao no servidor.
 *
 * A camera so' inicia no clique do botao "Usar a camera" (nunca automatico)
 * - exigencia do iOS Safari (sem autoplay de video) e boa pratica de nao
 * disparar o prompt de permissao sem contexto.
 */
(function () {
    'use strict';

    function pararStream(stream) {
        if (!stream) { return; }
        stream.getTracks().forEach(function (track) { track.stop(); });
    }

    function mostrarResultado(container, valido, mensagem) {
        var resultado = container.querySelector('[data-leitor-resultado]');
        resultado.textContent = mensagem;
        resultado.className = 'leitor-codigo-resultado status-pill ' + (valido ? 'verde' : 'vermelho');
        resultado.hidden = false;
    }

    function enviarCodigo(container, codigo) {
        fetch(container.dataset.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.SI_CSRF_TOKEN },
            credentials: 'same-origin',
            body: JSON.stringify({ codigo: codigo })
        })
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                mostrarResultado(container, dados.valido === true, dados.mensagem || 'Não foi possível validar o código.');
            })
            .catch(function () {
                mostrarResultado(container, false, 'Não foi possível validar o código. Verifique sua conexão e tente novamente.');
            });
    }

    function iniciarCamera(container) {
        var videoWrap = container.querySelector('[data-leitor-video-wrap]');
        var video = container.querySelector('[data-leitor-video]');
        var botaoCamera = container.querySelector('[data-leitor-botao-camera]');
        var ativo = true;

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function (stream) {
                video.srcObject = stream;
                videoWrap.hidden = false;
                botaoCamera.hidden = true;

                var detector = new window.BarcodeDetector({ formats: ['qr_code'] });

                function detectar() {
                    if (!ativo) { return; }

                    detector.detect(video)
                        .then(function (codigosDetectados) {
                            if (!ativo) { return; }

                            if (codigosDetectados.length > 0) {
                                ativo = false;
                                pararStream(stream);
                                videoWrap.hidden = true;
                                enviarCodigo(container, codigosDetectados[0].rawValue);
                                return;
                            }

                            requestAnimationFrame(detectar);
                        })
                        .catch(function () {
                            if (ativo) { requestAnimationFrame(detectar); }
                        });
                }

                requestAnimationFrame(detectar);
            })
            .catch(function () {
                mostrarResultado(container, false, 'Não foi possível acessar a câmera. Use a digitação manual abaixo.');
            });
    }

    function inicializar(container) {
        var botaoCamera = container.querySelector('[data-leitor-botao-camera]');
        var input = container.querySelector('[data-leitor-input]');
        var botaoValidar = container.querySelector('[data-leitor-botao-validar]');

        if ('BarcodeDetector' in window && navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            botaoCamera.hidden = false;
            botaoCamera.addEventListener('click', function () {
                iniciarCamera(container);
            });
        }

        input.addEventListener('input', function () {
            input.value = input.value.toUpperCase();
        });

        botaoValidar.addEventListener('click', function () {
            var codigo = input.value.trim();

            if (codigo === '') {
                mostrarResultado(container, false, 'Informe o código.');
                return;
            }

            enviarCodigo(container, codigo);
        });
    }

    document.querySelectorAll('[data-leitor-codigo]').forEach(function (container) {
        inicializar(container);
    });
})();
