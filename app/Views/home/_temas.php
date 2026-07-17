<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$glifosIcone = [
    'sustentabilidade' => '🌱', 'acessibilidade' => '♿', 'inovacao' => '💡', 'tecnologia' => '💻',
    'saude' => '⚕️', 'educacao' => '📚', 'seguranca' => '🔒', 'comunidade' => '🤝',
];
?>
<?php if (!empty($temasPorTrilha)): ?>
<section class="site-section" id="temas">
    <h2 class="section-title">Desafios</h2>
    <?php foreach ($temasPorTrilha as $grupo): ?>
        <?php if (!empty($grupo['temas'])): ?>
            <h3 class="temas-trilha-titulo"><?php echo htmlspecialchars($grupo['trilha']['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="temas-grid">
                <?php foreach ($grupo['temas'] as $tema): ?>
                    <div class="tema-card">
                        <?php if (!empty($tema['icone']) && isset($glifosIcone[$tema['icone']])): ?>
                            <span class="tema-icone" aria-hidden="true"><?php echo $glifosIcone[$tema['icone']]; ?></span>
                        <?php endif; ?>
                        <h4 class="tema-nome"><?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <?php if (!empty($tema['descricao_longa'])): ?>
                            <p class="tema-descricao"><?php echo nl2br(htmlspecialchars($tema['descricao_longa'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</section>
<?php endif; ?>
