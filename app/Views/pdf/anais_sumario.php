<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 54: sumario do volume dos Anais, em HTML para o Dompdf
 * (EventoAnaisGeradorService). $grupos: lista de ['eixo' => nome ou vazio,
 * 'itens' => [['titulo', 'autores', 'pagina']]], na ordem do volume. So'
 * nomes de autores: nunca CPF nem e-mail.
 */
$esc = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 2.5cm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5pt; line-height: 1.35; color: #111; }
    h1 { text-align: center; font-size: 14pt; margin: 0 0 18pt 0; text-transform: uppercase; letter-spacing: 1px; }
    h2 { font-size: 11pt; margin: 14pt 0 6pt 0; padding-bottom: 2pt; border-bottom: 0.5pt solid #666; page-break-after: avoid; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 0 0 7pt 0; }
    td.pagina { width: 1.5cm; text-align: right; padding-left: 8pt; }
    .titulo-trabalho { font-weight: bold; }
    .autores { font-size: 9pt; color: #333; }
</style>
</head>
<body>
<h1>Sumário</h1>
<?php foreach ($grupos as $grupo): ?>
    <?php if ($grupo['eixo'] !== ''): ?>
        <h2><?php echo $esc($grupo['eixo']); ?></h2>
    <?php endif; ?>
    <table>
        <?php foreach ($grupo['itens'] as $item): ?>
            <tr id="sumario-item-<?php echo (int) $item['indice']; ?>">
                <td>
                    <div class="titulo-trabalho"><?php echo $esc($item['titulo']); ?></div>
                    <?php if ($item['autores'] !== ''): ?>
                        <div class="autores"><?php echo $esc($item['autores']); ?></div>
                    <?php endif; ?>
                </td>
                <td class="pagina"><?php echo (int) $item['pagina']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>
</body>
</html>
