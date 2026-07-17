<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Slideshow de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('slides/novo/' . (int) $concurso['id']); ?>" class="btn-acao">+ Novo slide</a>
    </div>
</div>

<?php if (empty($slides)): ?>
    <p>Nenhum slide cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="slides/reordenar/<?php echo (int) $concurso['id']; ?>">
        <?php foreach ($slides as $indice => $slide): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $slide['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $slide['imagem_desktop_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width:90px;height:50px;object-fit:cover;border-radius:4px;">
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars(strip_tags((string) $slide['titulo_html']) ?: '(sem título)', ENT_QUOTES, 'UTF-8'); ?></strong>
                <br>
                <span class="status-pill <?php echo $slide['ativo'] ? 'verde' : 'vermelho'; ?>"><?php echo $slide['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('slides/editar/' . (int) $slide['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('slides/remover'); ?>" onsubmit="return confirm('Remover este slide?');">
                    <input type="hidden" name="id" value="<?php echo (int) $slide['id']; ?>">
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
                <button type="button" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($slides) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
