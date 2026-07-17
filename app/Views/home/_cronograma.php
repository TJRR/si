<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$agora = time();
$rotulosStatus = ['futuro' => 'Futuro', 'andamento' => 'Em andamento', 'concluido' => 'Concluído'];

foreach ($cronograma as &$itemCronogramaComStatus) {
    $inicio = strtotime((string) $itemCronogramaComStatus['data_inicio']);
    $fim = $itemCronogramaComStatus['data_fim'] !== null ? strtotime((string) $itemCronogramaComStatus['data_fim']) : $inicio;

    if ($agora < $inicio) {
        $itemCronogramaComStatus['status'] = 'futuro';
    } elseif ($agora > $fim) {
        $itemCronogramaComStatus['status'] = 'concluido';
    } else {
        $itemCronogramaComStatus['status'] = 'andamento';
    }
}
unset($itemCronogramaComStatus);
?>
<section class="site-section site-section-alt" id="cronograma">
    <div class="site-section-inner">
        <h2 class="section-title">Cronograma</h2>
        <p class="site-cronograma-fuso">Horários no fuso de Roraima (GMT-4).</p>
        <?php if (empty($cronograma)): ?>
            <p class="section-text">Cronograma em definição.</p>
        <?php else: ?>
            <ol class="timeline">
                <?php foreach ($cronograma as $item): ?>
                <?php $status = $item['status']; ?>
                <li class="timeline-item timeline-item-<?php echo $status; ?>">
                    <span class="status-pill <?php echo $status === 'concluido' ? 'verde' : ($status === 'andamento' ? 'laranja' : ''); ?>"><?php echo $rotulosStatus[$status]; ?></span>
                    <?php if ($item['trilha_nome'] !== null): ?>
                        <span class="timeline-trilha"><?php echo htmlspecialchars($item['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <strong class="timeline-nome"><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="timeline-datas">
                        <?php echo htmlspecialchars(formatarData($item['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($item['data_fim']): ?>
                            a <?php echo htmlspecialchars(formatarData($item['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </span>
                    <?php if (!empty($item['descricao'])): ?>
                        <p class="timeline-descricao"><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</section>
