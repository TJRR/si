<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if (!empty($etapasComResultadoPublicado)): ?>
    <section class="site-section" id="resultados">
        <h2 class="section-title">Resultados</h2>
        <ul>
            <?php foreach ($etapasComResultadoPublicado as $item): ?>
                <li>
                    <a href="<?php echo url('resultadosPublicos/etapa/' . (int) $item['etapa_id']); ?>">
                        <?php echo htmlspecialchars($item['trilha_nome'] . ' — ' . $item['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
