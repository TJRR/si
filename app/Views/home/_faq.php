<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if (!empty($faqAtivas)): ?>
<section class="site-section site-section-alt" id="faq">
    <div class="site-section-inner">
        <h2 class="section-title">Perguntas Frequentes</h2>
        <div class="site-faq-lista">
            <?php foreach ($faqAtivas as $faq): ?>
                <details class="site-faq-item">
                    <summary>
                        <?php echo htmlspecialchars($faq['pergunta'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($faq['categoria'])): ?>
                            <span class="status-pill"><?php echo htmlspecialchars($faq['categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </summary>
                    <div class="site-faq-resposta"><?php echo nl2br(htmlspecialchars($faq['resposta'], ENT_QUOTES, 'UTF-8')); ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
