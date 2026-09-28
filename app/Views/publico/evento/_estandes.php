<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 54: estandes ativos do evento, em cartoes no mesmo estilo dos
// Destaques (logotipo, categoria, nome e descricao). O codigo de visita
// nunca aparece na pagina publica, so' no cartaz impresso do estande.
$categoriasEstande = \App\Repositories\EstandeRepository::CATEGORIAS;
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($itensSecao)): ?>
            <div class="evento-destaques evento-colunas-4">
                <?php foreach ($itensSecao as $indiceItem => $item): ?>
                    <article class="evento-destaque" data-evento-entrada style="--ordem-entrada:<?php echo (int) $indiceItem; ?>;">
                        <?php if (!empty($item['logotipo_path'])): ?>
                            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $item['logotipo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $item['logotipo_alt'], ENT_QUOTES, 'UTF-8'); ?>" style="display:block;max-width:100%;height:72px;object-fit:contain;margin:0 0 12px;" loading="lazy">
                        <?php endif; ?>
                        <?php if (isset($categoriasEstande[$item['categoria']])): ?>
                            <p class="evento-destaque-quando"><?php echo htmlspecialchars($categoriasEstande[$item['categoria']], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if (!empty($item['descricao_html'])): ?>
                            <div class="evento-destaque-descricao"><?php echo sanitizarHtmlRico((string) $item['descricao_html']); ?></div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
