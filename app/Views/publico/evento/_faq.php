<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($itensSecao)): ?>
            <div class="site-faq-lista">
                <?php foreach ($itensSecao as $item): ?>
                    <details class="site-faq-item">
                        <summary><?php echo htmlspecialchars($item['pergunta'], ENT_QUOTES, 'UTF-8'); ?></summary>
                        <div class="site-faq-resposta"><?php echo $item['resposta_html']; ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
