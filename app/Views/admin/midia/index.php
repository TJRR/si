<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Biblioteca de mídia</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('midia/novo'); ?><?php echo !empty($pastaAtual) ? '?pasta=' . (int) $pastaAtual['id'] : ''; ?>" class="btn-acao">+ Nova mídia</a>
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

<?php
// Fase 51: navegacao por pastas. A raiz e' a propria biblioteca como sempre
// foi; tudo que ja existia continua la, porque a pasta nasce nula.
$pastaAtualId = $pastaAtual !== null ? (int) $pastaAtual['id'] : null;
$sufixoTipo = $tipoFiltro !== null ? '&tipo=' . urlencode($tipoFiltro) : '';
?>
<p class="midia-caminho">
    <a href="<?php echo url('midia/index'); ?><?php echo $tipoFiltro !== null ? '?tipo=' . urlencode($tipoFiltro) : ''; ?>">Biblioteca</a>
    <?php foreach ($caminho as $pastaDoCaminho): ?>
        &rsaquo; <a href="<?php echo url('midia/index'); ?>?pasta=<?php echo (int) $pastaDoCaminho['id']; ?><?php echo $sufixoTipo; ?>"><?php echo htmlspecialchars($pastaDoCaminho['nome'], ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endforeach; ?>
</p>

<?php if (!empty($subpastas)): ?>
    <ul class="midia-pastas">
        <?php foreach ($subpastas as $subpasta): ?>
            <li>
                <a href="<?php echo url('midia/index'); ?>?pasta=<?php echo (int) $subpasta['id']; ?><?php echo $sufixoTipo; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <?php echo htmlspecialchars($subpasta['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <form method="post" action="<?php echo url('midia/pastaRenomear'); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $subpasta['id']; ?>">
                    <label>Renomear: <input type="text" name="nome" maxlength="120" value="<?php echo htmlspecialchars($subpasta['nome'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
                <form method="post" action="<?php echo url('midia/pastaRemover'); ?>" onsubmit="return confirm('Remover esta pasta?');"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $subpasta['id']; ?>">
                    <button type="submit" class="btn-icone" title="Remover pasta">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        </svg>
                    </button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?php echo url('midia/pastaNova'); ?>" class="secao-linha-form"><?= campoCsrf() ?>
    <input type="hidden" name="pasta_pai_id" value="<?php echo $pastaAtualId !== null ? $pastaAtualId : ''; ?>">
    <label>Nova pasta aqui: <input type="text" name="nome" maxlength="120" required></label>
    <button type="submit" class="btn-acao">Criar pasta</button>
</form>

<?php if (empty($midias)): ?>
    <p>Nenhuma mídia nesta pasta.</p>
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
            <form method="post" action="<?php echo url('midia/mover'); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                <input type="hidden" name="id" value="<?php echo (int) $midia['id']; ?>">
                <input type="hidden" name="pasta_atual" value="<?php echo $pastaAtualId !== null ? $pastaAtualId : ''; ?>">
                <label>Mover para:
                    <select name="pasta_id">
                        <option value="">Biblioteca (raiz)</option>
                        <?php foreach ($todasAsPastas as $pastaDestino): ?>
                            <option value="<?php echo (int) $pastaDestino['id']; ?>" <?php echo (int) $midia['pasta_id'] === (int) $pastaDestino['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($pastaDestino['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn-acao">Mover</button>
            </form>
            <div class="acoes-icones">
                <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $midia['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Abrir" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('midia/remover'); ?>" onsubmit="return confirm('Remover esta mídia?');"><?= campoCsrf() ?>
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
