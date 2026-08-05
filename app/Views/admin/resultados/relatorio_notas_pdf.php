<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #191919; }
    h1 { font-size: 16px; margin: 0 0 4px 0; }
    h4 { font-size: 11px; margin: 14px 0 6px 0; }
    .meta { color: #555; margin: 0 0 10px 0; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
    th, td { border: 1px solid #999; padding: 2px 4px; font-size: 8px; text-align: center; }
    th { background: #f0f0f0; }
    .col-equipe { text-align: left; width: 130px; }
    .col-esquerda { text-align: left; }
    .col-final { font-weight: bold; }
    .nao-classificada { color: #888; }
</style>
</head>
<body>

<h1>Relatório de notas — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p class="meta">
    <?php echo htmlspecialchars($trilha !== null ? $trilha['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>
    &middot; Gerado em <?php echo htmlspecialchars($geradoEm, ENT_QUOTES, 'UTF-8'); ?>
    &middot; Iniciais dos avaliadores identificadas na legenda ao final.
</p>

<table>
    <tr>
        <th rowspan="2" class="col-equipe">Equipe</th>
        <?php foreach ($avaliadores as $avaliador): ?>
            <th colspan="<?php echo count($criterios); ?>"><?php echo htmlspecialchars($avaliador['iniciais'], ENT_QUOTES, 'UTF-8'); ?></th>
        <?php endforeach; ?>
        <th rowspan="2">Nota final</th>
        <th rowspan="2">Classificado</th>
    </tr>
    <tr>
        <?php foreach ($avaliadores as $avaliador): ?>
            <?php foreach ($criterios as $criterio): ?>
                <th title="<?php echo htmlspecialchars($avaliador['nome'] . ' — ' . $criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tr>
    <?php foreach ($ranking as $linha): ?>
        <?php
        $submissaoId = (int) $linha['submissao_id'];
        $notasDaSubmissao = isset($notaPorSubmissaoUsuarioCriterio[$submissaoId]) ? $notaPorSubmissaoUsuarioCriterio[$submissaoId] : [];
        $classificado = !empty($linha['classificado']);
        ?>
        <tr class="<?php echo $classificado ? '' : 'nao-classificada'; ?>">
            <td class="col-equipe"><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : ('Equipe #' . $linha['equipe_id']), ENT_QUOTES, 'UTF-8'); ?></td>
            <?php foreach ($avaliadores as $avaliador): ?>
                <?php $notasDoAvaliador = isset($notasDaSubmissao[$avaliador['usuario_id']]) ? $notasDaSubmissao[$avaliador['usuario_id']] : null; ?>
                <?php foreach ($criterios as $criterio): ?>
                    <?php $nota = $notasDoAvaliador !== null && isset($notasDoAvaliador[(int) $criterio['id']]) ? $notasDoAvaliador[(int) $criterio['id']] : null; ?>
                    <?php if ($nota !== null): ?>
                        <?php
                        $min = (float) $criterio['escala_min'];
                        $max = (float) $criterio['escala_max'];
                        $pct = $max > $min ? (((float) $nota - $min) / ($max - $min)) : 0;
                        $pct = max(0, min(1, $pct));
                        $matiz = (int) round(120 * $pct);
                        ?>
                        <td style="background: hsl(<?php echo $matiz; ?>, 65%, 82%);"><?php echo number_format((float) $nota, 1, ',', '.'); ?></td>
                    <?php else: ?>
                        <td>—</td>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <td class="col-final"><?php echo $linha['ne'] !== null ? number_format((float) $linha['ne'], 2, ',', '.') : '—'; ?></td>
            <td><?php echo $classificado ? 'Sim' : 'Não'; ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h4>Legenda dos avaliadores</h4>
<table>
    <tr><th>Iniciais</th><th class="col-esquerda">Nome completo</th></tr>
    <?php foreach ($avaliadores as $avaliador): ?>
        <tr>
            <td><?php echo htmlspecialchars($avaliador['iniciais'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="col-esquerda"><?php echo htmlspecialchars($avaliador['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h4>Critérios (peso de cada um na nota final)</h4>
<table>
    <tr><th>Código</th><th class="col-esquerda">Critério</th><th>Peso</th></tr>
    <?php foreach ($criterios as $criterio): ?>
        <tr>
            <td><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="col-esquerda"><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo rtrim(rtrim(number_format((float) $criterio['peso'], 2, ',', ''), '0'), ','); ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
