<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 54: paginas iniciais do volume dos Anais, em HTML para o Dompdf
 * (EventoAnaisGeradorService). Os textos ricos chegam ja filtrados; o
 * restante e' escapado aqui. Cada parte comeca numa pagina nova, e a ultima
 * nao leva quebra (senao o Dompdf cria uma pagina em branco no fim).
 */
$esc = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};

$linhaMembro = function (array $membro) {
    $partes = [];

    foreach (['nome', 'funcao', 'instituicao'] as $campo) {
        $valor = isset($membro[$campo]) ? trim((string) $membro[$campo]) : '';

        if ($valor !== '') {
            $partes[] = $valor;
        }
    }

    return implode(', ', $partes);
};

$partesDoVolume = [];

if ($gerarCapa) {
    $partesDoVolume[] = 'capa';
}

$partesDoVolume[] = 'rosto';

if ($fichaHtml !== '') {
    $partesDoVolume[] = 'ficha';
}

if ($expedienteHtml !== '') {
    $partesDoVolume[] = 'expediente';
}

if (!empty($comissoes)) {
    $partesDoVolume[] = 'comissoes';
}

if ($apresentacaoHtml !== '') {
    $partesDoVolume[] = 'apresentacao';
}

$ultimaParte = count($partesDoVolume) - 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 2.5cm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.45; color: #111; }
    .quebra { page-break-after: always; }
    table.moldura { width: 100%; border-collapse: collapse; }
    table.moldura td { padding: 0; text-align: center; }
    td.moldura-topo { height: 5cm; vertical-align: top; }
    td.moldura-meio { height: 13cm; vertical-align: middle; }
    td.moldura-base { height: 5cm; vertical-align: bottom; }
    td.verso { height: 23cm; vertical-align: bottom; text-align: left; }
    .evento-capa { font-size: 13pt; text-transform: uppercase; letter-spacing: 1px; }
    .titulo-capa { font-size: 22pt; font-weight: bold; line-height: 1.25; margin-bottom: 12pt; }
    .subtitulo-capa { font-size: 14pt; }
    .titulo-rosto { font-size: 18pt; font-weight: bold; line-height: 1.25; margin-bottom: 10pt; }
    .subtitulo-rosto { font-size: 13pt; }
    .dados-base p { margin: 0 0 4pt 0; }
    .ficha { border: 0.75pt solid #000; padding: 8pt 10pt; font-size: 9pt; line-height: 1.3; }
    h2.secao { text-align: center; font-size: 14pt; margin: 0 0 16pt 0; text-transform: uppercase; letter-spacing: 1px; }
    h2.comissao { text-align: center; font-size: 12.5pt; margin: 0 0 10pt 0; page-break-after: avoid; }
    .bloco-comissao { margin-bottom: 20pt; }
    p.membro { text-align: center; margin: 0 0 4pt 0; }
    .texto-rico p { margin: 0 0 8pt 0; }
    .apresentacao { text-align: justify; }
    img { max-width: 100%; }
</style>
</head>
<body>
<?php foreach ($partesDoVolume as $indiceParte => $parte): ?>
<div class="<?php echo $indiceParte < $ultimaParte ? 'quebra' : ''; ?>">
    <?php if ($parte === 'capa'): ?>
        <table class="moldura">
            <tr><td class="moldura-topo"><div class="evento-capa"><?php echo $esc($eventoNome); ?></div></td></tr>
            <tr>
                <td class="moldura-meio">
                    <div class="titulo-capa"><?php echo $esc($titulo); ?></div>
                    <?php if ($subtitulo !== ''): ?>
                        <div class="subtitulo-capa"><?php echo $esc($subtitulo); ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="moldura-base dados-base">
                    <?php if ($localAno !== ''): ?><p><?php echo $esc($localAno); ?></p><?php endif; ?>
                    <?php if ($identificador !== ''): ?><p><?php echo $esc($identificador); ?></p><?php endif; ?>
                </td>
            </tr>
        </table>
    <?php elseif ($parte === 'rosto'): ?>
        <table class="moldura">
            <tr>
                <td class="moldura-topo">
                    <?php if ($organizadoresHtml !== ''): ?><div class="texto-rico"><?php echo $organizadoresHtml; ?></div><?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="moldura-meio">
                    <div class="titulo-rosto"><?php echo $esc($titulo); ?></div>
                    <?php if ($subtitulo !== ''): ?>
                        <div class="subtitulo-rosto"><?php echo $esc($subtitulo); ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="moldura-base dados-base">
                    <?php if ($localAno !== ''): ?><p><?php echo $esc($localAno); ?></p><?php endif; ?>
                    <?php if ($identificador !== ''): ?><p><?php echo $esc($identificador); ?></p><?php endif; ?>
                </td>
            </tr>
        </table>
    <?php elseif ($parte === 'ficha'): ?>
        <table class="moldura">
            <tr><td class="verso"><div class="ficha texto-rico"><?php echo $fichaHtml; ?></div></td></tr>
        </table>
    <?php elseif ($parte === 'expediente'): ?>
        <h2 class="secao">Expediente</h2>
        <div class="texto-rico"><?php echo $expedienteHtml; ?></div>
    <?php elseif ($parte === 'comissoes'): ?>
        <?php foreach ($comissoes as $comissao): ?>
            <div class="bloco-comissao">
                <h2 class="comissao"><?php echo $esc($comissao['nome']); ?></h2>
                <?php foreach ($comissao['membros'] as $membro): ?>
                    <p class="membro"><?php echo $esc($linhaMembro($membro)); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php elseif ($parte === 'apresentacao'): ?>
        <h2 class="secao">Apresentação</h2>
        <div class="texto-rico apresentacao"><?php echo $apresentacaoHtml; ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</body>
</html>
