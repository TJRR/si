<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Biblioteca de mídia</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('midia/novo'); ?>" class="btn-acao">+ Nova mídia</a>
    </div>
</div>

<div class="filtros-barra-wrapper">
    <div class="filtros-barra">
        <a href="<?php echo url('midia/index'); ?>" class="btn-bordered<?php echo $tipoFiltro === null ? ' active' : ''; ?>">Todas</a>
        <a href="<?php echo url('midia/index'); ?>?tipo=imagem" class="btn-bordered<?php echo $tipoFiltro === 'imagem' ? ' active' : ''; ?>">Imagens</a>
        <a href="<?php echo url('midia/index'); ?>?tipo=pdf" class="btn-bordered<?php echo $tipoFiltro === 'pdf' ? ' active' : ''; ?>">PDFs</a>
        <a href="<?php echo url('midia/index'); ?>?tipo=video" class="btn-bordered<?php echo $tipoFiltro === 'video' ? ' active' : ''; ?>">Vídeos</a>
    </div>
</div>

<?php if (empty($midias)): ?>
    <p>Nenhuma mídia cadastrada ainda.</p>
<?php else: ?>
    <div class="admin-dashboard-cards">
        <?php foreach ($midias as $midia): ?>
        <div class="admin-card">
            <?php if ($midia['tipo'] === 'imagem'): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $midia['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $midia['alt_text'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:120px;object-fit:cover;border-radius:6px;">
            <?php else: ?>
                <p style="text-align:center;padding:2rem 0;background:var(--cor-fundo-alt);border-radius:6px;"><?php echo strtoupper($midia['tipo']); ?></p>
            <?php endif; ?>
            <p><strong><?php echo htmlspecialchars((string) $midia['titulo'] ?: '(sem título)', ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <div class="acoes-icones">
                <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $midia['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Abrir" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('midia/remover'); ?>" onsubmit="return confirm('Remover esta mídia?');">
                    <input type="hidden" name="id" value="<?php echo (int) $midia['id']; ?>">
                    <button type="submit" class="btn-icone" title="Remover">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
