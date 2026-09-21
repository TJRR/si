<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 43: parcial reaproveitavel do componente de leitura de codigo -
 * camera (BarcodeDetector nativo, so' Chrome/Edge) + digitacao manual do
 * codigo de 6 caracteres (sempre disponivel, unico caminho em Safari/
 * Firefox). $leitorEndpoint (obrigatorio) e' a URL do POST que recebe o
 * codigo; $leitorTitulo/$leitorInstrucao (opcionais) permitem que as Fases
 * 47/49/50 reaproveitem esta mesma view com textos proprios, sem duplicar
 * marcacao. Toda a logica de camera/deteccao/envio mora em
 * assets/js/leitor-codigo.js - este parcial so' declara o HTML e os
 * atributos data-* que o script le.
 */
$leitorTitulo = isset($leitorTitulo) ? $leitorTitulo : 'Ler código de credenciamento';
$leitorInstrucao = isset($leitorInstrucao) ? $leitorInstrucao : 'Use a câmera (quando disponível no seu navegador) ou digite o código abaixo.';
?>
<div class="leitor-codigo" data-leitor-codigo data-endpoint="<?php echo htmlspecialchars($leitorEndpoint, ENT_QUOTES, 'UTF-8'); ?>">
    <h2><?php echo htmlspecialchars($leitorTitulo, ENT_QUOTES, 'UTF-8'); ?></h2>
    <p><?php echo htmlspecialchars($leitorInstrucao, ENT_QUOTES, 'UTF-8'); ?></p>

    <div class="leitor-codigo-video-wrap" hidden data-leitor-video-wrap>
        <video class="leitor-codigo-video" data-leitor-video autoplay playsinline muted></video>
    </div>

    <button type="button" class="btn leitor-codigo-botao-camera" hidden data-leitor-botao-camera>Usar a câmera</button>

    <div class="leitor-codigo-manual">
        <label for="leitor-codigo-input">Código de 5 ou 6 caracteres</label>
        <input type="text" id="leitor-codigo-input" class="leitor-codigo-input" maxlength="6" autocomplete="off" autocapitalize="characters" data-leitor-input>
        <button type="button" class="app-btn-acao" data-leitor-botao-validar>Validar</button>
    </div>

    <div class="leitor-codigo-resultado" hidden data-leitor-resultado></div>
</div>
