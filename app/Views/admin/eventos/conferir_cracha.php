<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Conferir crachá: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p style="color:#555;font-size:0.9em;">Para a organização conferir, na entrada, o crachá impresso ou o código que o participante mostra no aplicativo. A leitura só mostra de quem é a inscrição e se ela está homologada: não registra conexão, presença nem credenciamento, e não dá pontos a ninguém.</p>

<?php
$leitorEndpoint = url('eventos/validarCracha/' . (int) $evento['id']);
$leitorTitulo = 'Ler o código do crachá';
$leitorInstrucao = 'Aponte a câmera para o código do crachá ou da tela do aplicativo do participante, ou digite-o abaixo.';
$leitorRotuloCampo = 'Código de 6 caracteres do crachá';
require __DIR__ . '/../../eventoApp/_leitor_codigo.php';
?>
