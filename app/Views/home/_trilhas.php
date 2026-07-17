<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<section class="site-section" id="trilhas">
    <h2 class="section-title">Trilhas</h2>
    <?php if (empty($trilhasAtivas)): ?>
        <p class="section-text">Trilhas em definição.</p>
    <?php else: ?>
        <div class="site-trilhas-grid">
            <?php foreach ($trilhasAtivas as $trilha): ?>
            <?php
            $documentosDaTrilha = array_values(array_filter($documentos, function ($documento) use ($trilha) {
                return (int) $documento['trilha_id'] === (int) $trilha['id'];
            }));
            $inscricaoDaTrilha = null;
            foreach ($trilhasComInscricaoAberta as $item) {
                if ($item['trilha_nome'] === $trilha['nome']) {
                    $inscricaoDaTrilha = $item;
                    break;
                }
            }
            ?>
            <div class="admin-card site-trilha-card">
                <h3><?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if (!empty($trilha['descricao'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($trilha['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php endif; ?>
                <?php if (!empty($documentosDaTrilha)): ?>
                    <ul class="site-trilha-documentos">
                        <?php foreach ($documentosDaTrilha as $documento): ?>
                            <li><a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $documento['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($inscricaoDaTrilha !== null): ?>
                    <a href="<?php echo url('inscricao/formulario/' . (int) $inscricaoDaTrilha['etapa_id']); ?>" class="btn btn-cta">Inscreva-se — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
