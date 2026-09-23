<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($itensSecao)): ?>
            <ol class="evento-cronograma">
                <?php foreach ($itensSecao as $item): ?>
                    <li>
                        <span class="evento-marcador" style="<?php echo !empty($item['cor']) ? 'background:' . htmlspecialchars($item['cor'], ENT_QUOTES, 'UTF-8') . ';' : ''; ?>" aria-hidden="true"></span>
                        <strong><?php echo htmlspecialchars($item['periodo_texto'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
