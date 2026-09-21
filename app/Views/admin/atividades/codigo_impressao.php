<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 47: modelo de impressao do codigo fixo da atividade - pagina solta,
 * sem layout.php (mesmo espirito de eventoApp/cracha.php, Fase 42), para
 * afixar no espaco fisico onde a atividade ocorre. Por nao passar por
 * layout.php, todo valor abaixo e' escapado explicitamente aqui.
 */
// Fase 48B: documento institucional impresso, sempre no tema padrao do
// sistema, nunca no tema pessoal de quem gerou o cartaz.
$corPrimaria = (new \App\Repositories\TemaVisualRepository())->buscarPadrao()['cor_primaria_inicio'];
$corTextoFaixa = corContrastante($corPrimaria);

$qrSvg = \App\Services\QrCodeService::renderizarSvg($atividade['codigo_atividade']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Código: <?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        /* Fase 47 (correcao pos-teste de fumaca): cartaz da SALA precisa
           ocupar a folha A4 inteira, pra ser lido de longe quando afixado -
           diferente do cracha individual do participante (eventoApp/cracha.php,
           Fase 42), que continua no tamanho pequeno original e NAO foi
           alterado por esta correcao. */
        @page { size: A4; margin: 0; }
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
        .cartaz {
            width: 210mm;
            height: 297mm;
            background: #fff;
            border: 1px solid #ccc;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
        }
        .cartaz-faixa {
            background: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;
            color: <?php echo htmlspecialchars($corTextoFaixa, ENT_QUOTES, 'UTF-8'); ?>;
            padding: 24mm 16mm;
            text-align: center;
        }
        .cartaz-evento {
            font-size: 28px;
            margin: 0;
        }
        .cartaz-corpo {
            flex: 1;
            padding: 16mm;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .cartaz-nome {
            font-size: 52px;
            font-weight: bold;
            margin: 0 0 16px;
            word-break: break-word;
            line-height: 1.15;
        }
        .cartaz-info {
            font-size: 26px;
            color: #555;
            margin: 0 0 32px;
        }
        .cartaz-qr {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
        }
        .cartaz-qr svg {
            width: 340px;
            height: 340px;
        }
        .cartaz-codigo {
            font-family: 'Courier New', Courier, monospace;
            font-size: 28px;
            letter-spacing: 2px;
            color: #555;
            margin: 0;
        }
        .cartaz-instrucao {
            font-size: 20px;
            color: #777;
            margin: 16px 0 0;
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
            .cartaz { box-shadow: none; border: none; width: auto; height: auto; min-height: 100vh; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="cartaz">
        <div class="cartaz-faixa">
            <p class="cartaz-evento"><?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="cartaz-corpo">
            <p class="cartaz-nome"><?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="cartaz-info">
                <?php if (!empty($atividade['local'])): ?>
                    <?php echo htmlspecialchars($atividade['local'], ENT_QUOTES, 'UTF-8'); ?><br>
                <?php endif; ?>
                <?php echo htmlspecialchars(formatarDataHora($atividade['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars(formatarDataHora($atividade['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div class="cartaz-qr"><?php echo $qrSvg; ?></div>
            <p class="cartaz-codigo"><?php echo htmlspecialchars($atividade['codigo_atividade'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="cartaz-instrucao">Aponte a câmera do aplicativo para confirmar presença.</p>
        </div>
    </div>
    <button type="button" class="botao-imprimir no-print" onclick="window.print()">Imprimir</button>
</body>
</html>
