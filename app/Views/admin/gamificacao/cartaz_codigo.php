<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 58: cartaz de um código fixo de 6 caracteres (credenciamento no local
 * e participação em competição) - página solta, sem layout.php, no molde de
 * admin/atividades/codigo_impressao.php (Fase 47), com o estilo em
 * assets/css/cartaz-codigo.css. Por não passar por layout.php, todo valor
 * abaixo é escapado explicitamente aqui. Sempre no tema padrão do sistema,
 * nunca no tema pessoal de quem gerou o cartaz: as duas cores entram como
 * variáveis de estilo na raiz da página.
 */
$corPrimaria = (new \App\Repositories\TemaVisualRepository())->buscarPadrao()['cor_primaria_inicio'];
$corTextoFaixa = corContrastante($corPrimaria);
$variaveisCores = '--cor-faixa:' . $corPrimaria . ';--cor-texto-faixa:' . $corTextoFaixa . ';';
$enderecoEstilo = config('base_path') . '/assets/css/cartaz-codigo.css?v=' . filemtime(__DIR__ . '/../../../../assets/css/cartaz-codigo.css');

$qrSvg = \App\Services\QrCodeService::renderizarSvg($codigo);
?>
<!DOCTYPE html>
<html lang="pt-br" style="<?php echo htmlspecialchars($variaveisCores, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Código: <?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>@import url("<?php echo htmlspecialchars($enderecoEstilo, ENT_QUOTES, 'UTF-8'); ?>");</style>
</head>
<body>
    <div class="cartaz">
        <div class="cartaz-faixa">
            <p class="cartaz-evento"><?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="cartaz-corpo">
            <p class="cartaz-nome"><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="cartaz-info"><?php echo htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="cartaz-qr"><?php echo $qrSvg; ?></div>
            <p class="cartaz-codigo"><?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="cartaz-instrucao"><?php echo htmlspecialchars($instrucao, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
    <button type="button" class="botao-imprimir no-print" onclick="window.print()">Imprimir</button>
</body>
</html>
