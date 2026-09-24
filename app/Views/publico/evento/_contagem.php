<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51, refeita na reabertura: contagem regressiva em duas colunas. A
// esquerda, o circulo escuro com os dias e o relogio, cercado por tres
// aneis pulsantes com atraso escalonado; a direita, etiqueta, titulo e as
// datas em destaque. Sem data alvo cadastrada, vale a data de inicio do
// evento as 00h00.
$dataAlvo = !empty($dadosSecao['data_alvo']) ? $dadosSecao['data_alvo'] : $evento['data_inicio'] . ' 00:00:00';
$estiloRelogio = estiloDeCores(null, null, [
    '--cor-circulo' => isset($dadosSecao['cor_circulo']) ? $dadosSecao['cor_circulo'] : null,
    '--cor-anel-1' => isset($dadosSecao['cor_anel_1']) ? $dadosSecao['cor_anel_1'] : null,
    '--cor-anel-2' => isset($dadosSecao['cor_anel_2']) ? $dadosSecao['cor_anel_2'] : null,
    '--cor-anel-3' => isset($dadosSecao['cor_anel_3']) ? $dadosSecao['cor_anel_3'] : null,
]);
$cabecalhoProprio = true;
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <div class="evento-contagem" data-contagem-alvo="<?php echo htmlspecialchars($dataAlvo, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="evento-contagem-relogio" style="<?php echo $estiloRelogio; ?>">
                <span class="evento-contagem-anel evento-contagem-anel-1" aria-hidden="true"></span>
                <span class="evento-contagem-anel evento-contagem-anel-2" aria-hidden="true"></span>
                <span class="evento-contagem-anel evento-contagem-anel-3" aria-hidden="true"></span>
                <div class="evento-contagem-circulo" role="timer" aria-live="off">
                    <strong data-contagem-dias>0</strong>
                    <span class="evento-contagem-rotulo-dias">dias</span>
                    <span class="evento-contagem-hora" data-contagem-hora>00h 00m 00s</span>
                </div>
            </div>

            <div class="evento-contagem-texto">
                <?php include __DIR__ . '/_secao_cabecalho.php'; ?>

                <?php if (!empty($itensSecao)): ?>
                    <ul class="evento-contagem-datas">
                        <?php foreach ($itensSecao as $item): ?>
                            <li>
                                <span class="evento-marcador" style="<?php echo estiloDeCores($item['cor_marcador']); ?>" aria-hidden="true"></span>
                                <span>
                                    <?php echo htmlspecialchars($item['texto'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($item['data_referencia'])): ?>
                                        · <?php echo htmlspecialchars(formatarData($item['data_referencia']), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
