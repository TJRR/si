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
    h2 { font-size: 13px; margin: 18px 0 6px 0; border-bottom: 1px solid #999; padding-bottom: 3px; }
    h3 { font-size: 11px; margin: 0 0 6px 0; }
    h4 { font-size: 10px; margin: 10px 0 4px 0; }
    .meta { color: #555; margin: 0 0 10px 0; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
    th, td { border: 1px solid #999; padding: 3px 5px; font-size: 8.5px; text-align: center; }
    th { background: #f0f0f0; }
    .col-equipe { text-align: left; width: 140px; }
    .col-esquerda { text-align: left; }
    .corte-linha td { text-align: center; font-style: italic; background: #fff3e8; }
    .nao-classificada { color: #888; }
    .bloco-equipe { margin-bottom: 16px; border: 1px solid #ccc; padding: 8px; }
    .ficha-item { margin-bottom: 6px; }
    .ficha-item-label { font-weight: bold; }
    .feedback-item { margin: 3px 0; padding: 4px; background: #f7f7f7; }
    ol.integrantes { margin: 0 0 8px 0; padding-left: 18px; }
</style>
</head>
<body>

<h1>Relatório de auditoria — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p class="meta">
    <?php echo htmlspecialchars($trilha !== null ? $trilha['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>
    &middot; Gerado em <?php echo htmlspecialchars($geradoEm, ENT_QUOTES, 'UTF-8'); ?>
</p>

<h2>1. Notas por avaliador e critério</h2>
<table>
    <tr>
        <th rowspan="2" class="col-equipe">Equipe</th>
        <?php foreach ($letrasColunas as $letraColuna): ?>
            <th colspan="<?php echo count($criterios); ?>">Avaliador <?php echo htmlspecialchars($letraColuna, ENT_QUOTES, 'UTF-8'); ?></th>
        <?php endforeach; ?>
    </tr>
    <tr>
        <?php foreach ($letrasColunas as $letraColuna): ?>
            <?php foreach ($criterios as $criterio): ?>
                <th title="<?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tr>
    <?php foreach ($ranking as $linha): ?>
        <?php
        $submissaoId = (int) $linha['submissao_id'];
        $letraLocalPorUsuario = $submissoesDetalhe[$submissaoId]['letraLocalPorUsuario'];
        $usuarioIdPorLetra = array_flip($letraLocalPorUsuario);
        $notasPorUsuarioCriterio = [];
        foreach ($submissoesDetalhe[$submissaoId]['notas'] as $nota) {
            $notasPorUsuarioCriterio[(int) $nota['usuario_id']][(int) $nota['criterio_avaliacao_id']] = $nota;
        }
        ?>
        <tr>
            <td class="col-equipe"><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : ('Equipe #' . $linha['equipe_id']), ENT_QUOTES, 'UTF-8'); ?></td>
            <?php foreach ($letrasColunas as $letraColuna): ?>
                <?php $usuarioId = isset($usuarioIdPorLetra[$letraColuna]) ? $usuarioIdPorLetra[$letraColuna] : null; ?>
                <?php foreach ($criterios as $criterio): ?>
                    <?php $nota = $usuarioId !== null && isset($notasPorUsuarioCriterio[$usuarioId][(int) $criterio['id']]) ? $notasPorUsuarioCriterio[$usuarioId][(int) $criterio['id']] : null; ?>
                    <?php if ($nota !== null): ?>
                        <?php
                        $min = (float) $criterio['escala_min'];
                        $max = (float) $criterio['escala_max'];
                        $pct = $max > $min ? (((float) $nota['nota'] - $min) / ($max - $min)) : 0;
                        $pct = max(0, min(1, $pct));
                        $matiz = (int) round(120 * $pct);
                        ?>
                        <td style="background: hsl(<?php echo $matiz; ?>, 65%, 82%);"><?php echo number_format((float) $nota['nota'], 1, ',', '.'); ?></td>
                    <?php else: ?>
                        <td>—</td>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>

<h4>Detalhamento dos critérios</h4>
<table>
    <tr><th>Código</th><th class="col-esquerda">Critério</th><th class="col-esquerda">Descrição</th></tr>
    <?php foreach ($criterios as $criterio): ?>
        <tr>
            <td><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="col-esquerda"><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="col-esquerda"><?php echo !empty($criterio['descricao']) ? nl2br(htmlspecialchars($criterio['descricao'], ENT_QUOTES, 'UTF-8')) : '—'; ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>2. Classificação</h2>
<table>
    <tr><th>Posição</th><th class="col-equipe">Equipe</th><th>Nota geral (NE)</th><th>Situação</th></tr>
    <?php $posicao = 0; $ultimoClassificado = null; ?>
    <?php foreach ($ranking as $linha): ?>
        <?php
        $posicao++;
        $classificado = (bool) (int) $linha['classificado'];
        ?>
        <?php if ($ultimoClassificado === true && $classificado === false): ?>
            <tr class="corte-linha"><td colspan="4">— linha de corte —</td></tr>
        <?php endif; ?>
        <?php $ultimoClassificado = $classificado; ?>
        <tr<?php echo !$classificado ? ' class="nao-classificada"' : ''; ?>>
            <td><?php echo $posicao; ?>º</td>
            <td class="col-equipe"><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : ('Equipe #' . $linha['equipe_id']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $linha['ne'] !== null ? number_format((float) $linha['ne'], 2, ',', '.') : '—'; ?></td>
            <td><?php echo $classificado ? 'Classificada' : 'Não classificada'; ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>3. Equipes classificadas — integrantes, submissão e avaliações</h2>
<?php foreach ($ranking as $linha): ?>
    <?php if (!((bool) (int) $linha['classificado'])) {
        continue;
    } ?>
    <?php $submissaoId = (int) $linha['submissao_id']; ?>
    <div class="bloco-equipe">
        <h3><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : ('Equipe #' . $linha['equipe_id']), ENT_QUOTES, 'UTF-8'); ?></h3>

        <?php if (!empty($submissoesDetalhe[$submissaoId]['integrantes'])): ?>
            <h4>Integrantes</h4>
            <ol class="integrantes">
                <?php foreach ($submissoesDetalhe[$submissaoId]['integrantes'] as $integrante): ?>
                    <li>
                        <?php echo htmlspecialchars($integrante['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php echo $integrante['papel'] === 'lider' ? ' (Líder)' : ''; ?>
                        <?php if (!empty($integrante['email'])): ?>
                            — <?php echo htmlspecialchars($integrante['email'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php foreach ($submissoesDetalhe[$submissaoId]['conteudo'] as $item): ?>
            <?php $campo = $item['campo']; $valor = $item['valor']; ?>
            <?php if ($valor === null || $valor === ''): ?>
                <?php continue; ?>
            <?php endif; ?>
            <?php if ($campo['tipo'] === 'grupo_participantes'): ?>
                <?php continue; // ja mostrado acima em "Integrantes" (fonte: cadastro homologado da equipe, nao o campo do formulario). ?>
            <?php endif; ?>
            <div class="ficha-item">
                <div class="ficha-item-label"><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php if ($campo['tipo'] === 'upload_pdf'): ?>
                    <div>Anexo em PDF: <?php echo htmlspecialchars($valor['nome_original'], ENT_QUOTES, 'UTF-8'); ?> (baixe pelo painel — não incluído neste relatório)</div>
                <?php else: ?>
                    <div><?php echo nl2br(htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8')); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <h4>Notas e feedback por avaliador</h4>
        <?php foreach ($submissoesDetalhe[$submissaoId]['letraLocalPorUsuario'] as $usuarioId => $letraAvaliador): ?>
            <?php
            $notasDoAvaliador = array_filter($submissoesDetalhe[$submissaoId]['notas'], function ($nota) use ($usuarioId) {
                return (int) $nota['usuario_id'] === $usuarioId;
            });
            ?>
            <?php if (empty($notasDoAvaliador)): ?>
                <?php continue; ?>
            <?php endif; ?>
            <p><strong>Avaliador <?php echo htmlspecialchars($letraAvaliador, ENT_QUOTES, 'UTF-8'); ?>:</strong></p>
            <ul>
                <?php foreach ($notasDoAvaliador as $nota): ?>
                    <li>
                        <?php echo htmlspecialchars($nota['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?>:
                        <?php echo number_format((float) $nota['nota'], 1, ',', '.'); ?>
                        <?php if (!empty($nota['feedback'])): ?>
                            — <em><?php echo htmlspecialchars($nota['feedback'], ENT_QUOTES, 'UTF-8'); ?></em>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php foreach ($submissoesDetalhe[$submissaoId]['feedbackSubmissao'] as $feedback): ?>
                <?php if ((int) $feedback['usuario_id'] === $usuarioId && !empty($feedback['feedback'])): ?>
                    <div class="feedback-item"><em><?php echo htmlspecialchars($feedback['feedback'], ENT_QUOTES, 'UTF-8'); ?></em></div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

</body>
</html>
