<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51: contagem regressiva. Sem data alvo cadastrada, vale a data de
// inicio do evento as 00h00 - a secao funciona sem configuracao extra.
$dataAlvo = !empty($dadosSecao['data_alvo']) ? $dadosSecao['data_alvo'] : $evento['data_inicio'] . ' 00:00:00';
$estiloCirculo = !empty($dadosSecao['cor_circulo']) ? 'background:' . htmlspecialchars($dadosSecao['cor_circulo'], ENT_QUOTES, 'UTF-8') . ';' : '';
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <div class="evento-contagem" data-contagem-alvo="<?php echo htmlspecialchars($dataAlvo, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="evento-contagem-relogio">
                <div class="evento-contagem-circulo" style="<?php echo $estiloCirculo; ?>">
                    <strong data-contagem-dias>0</strong>
                    <span>dias</span>
                </div>
                <p class="evento-contagem-hora" data-contagem-hora>00h 00m 00s</p>
            </div>

            <?php if (!empty($itensSecao)): ?>
                <ul class="evento-contagem-datas">
                    <?php foreach ($itensSecao as $item): ?>
                        <li>
                            <span class="evento-marcador" style="<?php echo !empty($item['cor_marcador']) ? 'background:' . htmlspecialchars($item['cor_marcador'], ENT_QUOTES, 'UTF-8') . ';' : ''; ?>" aria-hidden="true"></span>
                            <?php echo htmlspecialchars($item['texto'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($item['data_referencia'])): ?>
                                <strong><?php echo htmlspecialchars(formatarData($item['data_referencia']), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
