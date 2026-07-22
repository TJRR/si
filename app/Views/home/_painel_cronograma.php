<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 19 (#86): acesso rapido ao cronograma pelo icone do cabecalho, sem
// precisar rolar ate a secao #cronograma. Reaproveita o mesmo $cronograma
// ja montado por HomeController::index() - sem query nova. Lista simples e
// cronologica (sem colunas por trilha/status), igual ao site de referencia.
?>
<aside id="painel-cronograma" class="site-painel-lateral" aria-hidden="true" aria-label="Cronograma">
    <div class="site-painel-cabecalho">
        <h2>Cronograma</h2>
        <button type="button" class="site-painel-fechar" data-fechar-painel aria-label="Fechar">×</button>
    </div>
    <div class="site-painel-corpo">
        <?php if (empty($cronograma)): ?>
            <p>Cronograma em definição.</p>
        <?php else: ?>
            <?php foreach ($cronograma as $item): ?>
                <p class="site-painel-item">
                    <strong><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                    <?php echo htmlspecialchars(formatarData($item['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($item['data_fim']): ?>
                        a <?php echo htmlspecialchars(formatarData($item['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                    <?php if (!empty($item['descricao'])): ?>
                        <br><?php echo nl2br(htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8')); ?>
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</aside>
<div class="site-painel-backdrop" data-fechar-painel></div>
