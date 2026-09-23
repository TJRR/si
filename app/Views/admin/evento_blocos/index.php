<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Blocos de conteúdo: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventoBlocos/novo/' . (int) $evento['id']); ?>" class="btn-acao">+ Novo bloco</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">A posição de cada bloco na página, o liga e desliga e a entrada no menu ficam em <a href="<?php echo url('eventoSecoes/index/' . (int) $evento['id']); ?>">Seções da página</a>, junto com as demais seções do evento.</p>

<?php if (empty($blocos)): ?>
    <p>Nenhum bloco cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista">
        <?php foreach ($blocos as $bloco): ?>
        <li class="reordenar-item">
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($bloco['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <br>
                <span class="status-pill <?php echo $bloco['ativo'] ? 'verde' : 'vermelho'; ?>"><?php echo $bloco['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                <span style="color:var(--cor-texto-suave);font-size:.8rem;"> #<?php echo htmlspecialchars($bloco['secao_ancora'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('eventoBlocos/editar/' . (int) $bloco['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('eventoBlocos/remover'); ?>" onsubmit="return confirm('Remover este bloco?');"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $bloco['id']; ?>">
                    <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
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
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
