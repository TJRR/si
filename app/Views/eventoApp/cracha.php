<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 42: crachá de credenciamento pronto para impressão - página solta,
 * sem layout.php (mesmo espírito de politica.php/termos.php: sem app-bar,
 * sem menu, sem topbar do site). Por não passar por layout.php, esta view
 * não ganha a escapagem de saída automática de nenhum partial compartilhado
 * - todo valor abaixo é escapado explicitamente aqui.
 */
// Fase 48B: documento institucional impresso, sempre no tema padrao do
// sistema, nunca no tema pessoal de quem gerou o cracha.
$corPrimaria = (new \App\Repositories\TemaVisualRepository())->buscarPadrao()['cor_primaria_inicio'];
$corTextoFaixa = corContrastante($corPrimaria);

// Fase 42 (correcao pos-teste de fumaca): codigo encurtado pra 6 caracteres
// (Fase 46: alfabeto agora em CodigoUnicoService::ALFABETO_CROCKFORD_BASE32) -
// sem hifen, o agrupamento em blocos de 4 fazia sentido so' pro formato longo
// antigo.
$codigoFormatado = $inscricao['codigo_credenciamento'];
$qrSvg = \App\Services\QrCodeService::renderizarSvg($inscricao['codigo_credenciamento']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crachá: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #f2f2f2;
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        .cracha {
            width: 340px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }
        .cracha-faixa {
            background: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;
            color: <?php echo htmlspecialchars($corTextoFaixa, ENT_QUOTES, 'UTF-8'); ?>;
            padding: 14px 16px;
            text-align: center;
        }
        .cracha-icone {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-bottom: 6px;
        }
        .cracha-evento {
            font-size: 15px;
            font-weight: bold;
            margin: 0;
        }
        .cracha-corpo {
            padding: 20px 16px;
            text-align: center;
        }
        .cracha-nome {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 16px;
            word-break: break-word;
        }
        .cracha-qr {
            display: flex;
            justify-content: center;
            margin-bottom: 12px;
        }
        .cracha-qr svg {
            width: 200px;
            height: 200px;
        }
        .cracha-codigo {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            letter-spacing: 1px;
            color: #555;
            margin: 0;
        }
        .botao-imprimir {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            background: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;
            color: <?php echo htmlspecialchars($corTextoFaixa, ENT_QUOTES, 'UTF-8'); ?>;
            font-size: 15px;
            cursor: pointer;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .cracha { box-shadow: none; border: 1px solid #999; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="cracha">
        <div class="cracha-faixa">
            <img src="<?php echo htmlspecialchars(iconeAppUrl('icon-192.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="" class="cracha-icone">
            <p class="cracha-evento"><?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="cracha-corpo">
            <p class="cracha-nome"><?php echo htmlspecialchars($nomeParticipante, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="cracha-qr"><?php echo $qrSvg; ?></div>
            <p class="cracha-codigo"><?php echo htmlspecialchars($codigoFormatado, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
    <button type="button" class="botao-imprimir no-print" onclick="window.print()">Imprimir crachá</button>
</body>
</html>
