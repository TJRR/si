<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Banners de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('banners/novo/' . (int) $concurso['id']); ?>" class="btn-acao">+ Novo banner</a>
    </div>
</div>

<?php if (empty($banners)): ?>
    <p>Nenhum banner cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="banners/reordenar/<?php echo (int) $concurso['id']; ?>">
        <?php foreach ($banners as $indice => $banner): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $banner['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <?php if (!empty($banner['imagem_desktop_path'])): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $banner['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width:90px;height:36px;object-fit:cover;border-radius:4px;">
            <?php else: ?>
                <span style="width:90px;height:36px;border-radius:4px;display:inline-block;background:<?php echo htmlspecialchars((string) $banner['cor_fundo'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
            <?php endif; ?>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars(strip_tags((string) $banner['conteudo_html']) ?: '(sem texto)', ENT_QUOTES, 'UTF-8'); ?></strong>
                <br>
                <span class="status-pill <?php echo $banner['ativo'] ? 'verde' : 'vermelho'; ?>"><?php echo $banner['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('banners/editar/' . (int) $banner['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('banners/remover'); ?>" onsubmit="return confirm('Remover este banner?');">
                    <input type="hidden" name="id" value="<?php echo (int) $banner['id']; ?>">
                    <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
                    <button type="submit" class="btn-icone" title="Remover">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                            <path d="M10 11v6"></path>
                            <path d="M14 11v6"></path>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                        </svg>
                    </button>
                </form>
            </div>
            <div class="reordenar-botoes">
                <button type="button" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($banners) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
