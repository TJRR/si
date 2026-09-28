<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 54: cartaz A4 do estande (QR mais o codigo em texto), no molde do
 * cartaz de Atividade (admin/atividades/codigo_impressao.php). Pagina solta,
 * sem layout.php: todo valor e' escapado aqui. Usada pelo Administrador, pelo
 * Suporte e pelo representante do estande; quem chama ja conferiu o acesso,
 * esta tela nao busca nada sozinha. Sempre no tema padrao do sistema, nunca
 * no tema pessoal de quem imprime. O estilo fixo fica em
 * assets/css/cartaz-impressao.css.
 */
$corPrimaria = (new \App\Repositories\TemaVisualRepository())->buscarPadrao()['cor_primaria_inicio'];
// Seis digitos nas duas cores: estiloDeCores() so' aceita esse formato.
$corTextoFaixa = corContrastante($corPrimaria, '#ffffff', '#222222');
$estiloFaixa = estiloDeCores($corPrimaria, $corTextoFaixa);

$qrSvg = \App\Services\QrCodeService::renderizarSvg($estande['codigo_estande']);
$rotuloCategoria = isset(\App\Repositories\EstandeRepository::CATEGORIAS[$estande['categoria']])
    ? \App\Repositories\EstandeRepository::CATEGORIAS[$estande['categoria']]
    : '';
$arquivoEstilo = __DIR__ . '/../../../../assets/css/cartaz-impressao.css';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cartaz: <?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        @import url("<?php echo htmlspecialchars(config('base_path') . '/assets/css/cartaz-impressao.css?v=' . (is_file($arquivoEstilo) ? filemtime($arquivoEstilo) : 0), ENT_QUOTES, 'UTF-8'); ?>");
    </style>
</head>
<body>
    <div class="cartaz">
        <div class="cartaz-faixa" style="<?php echo $estiloFaixa; ?>">
            <p class="cartaz-evento"><?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="cartaz-corpo">
            <?php if (!empty($estande['logotipo_path'])): ?>
                <img class="cartaz-logotipo" src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $estande['logotipo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $estande['logotipo_alt'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <?php if ($rotuloCategoria !== ''): ?>
                <p class="cartaz-categoria"><?php echo htmlspecialchars($rotuloCategoria, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <p class="cartaz-nome"><?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="cartaz-qr"><?php echo $qrSvg; ?></div>
            <p class="cartaz-codigo"><?php echo htmlspecialchars($estande['codigo_estande'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="cartaz-instrucao">Leia este código no aplicativo do evento, em Estandes, para registrar a sua visita.</p>
        </div>
    </div>
    <button type="button" class="botao-imprimir no-print" style="<?php echo $estiloFaixa; ?>" onclick="window.print()">Imprimir</button>
</body>
</html>
