<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $rotulosTipo = \App\Repositories\EventoDocumentoRepository::ROTULOS_TIPO; ?>
<div class="pagina-titulo-acoes">
    <h1>Documentos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventoDocumentos/novo/' . (int) $evento['id']); ?>" class="btn-acao">+ Novo documento</a>
    </div>
</div>
<p>Um novo envio com o mesmo tipo e título vira uma nova versão: o arquivo anterior nunca é apagado, só deixa de ser o "atual". Veja o histórico completo pelo ícone de relógio.</p>
<p>Os botões da página pública que apontam para um documento (por exemplo, "Acessar o Edital completo") abrem sempre a versão atual. Documentos despublicados continuam salvos e listados aqui, mas o botão que aponta para eles some da página.</p>

<?php if (empty($documentos)): ?>
    <p>Nenhum documento cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoDocumentos/reordenar/' . (int) $evento['id']; ?>">
        <?php foreach ($documentos as $indice => $documento): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $documento['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars(isset($rotulosTipo[$documento['tipo']]) ? $rotulosTipo[$documento['tipo']] : $documento['tipo'], ENT_QUOTES, 'UTF-8'); ?>:</strong>
                <?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                <span class="status-pill">v<?php echo (int) $documento['versao']; ?></span>
                <?php if ((int) $documento['publicado'] === 1): ?>
                    <span class="status-pill verde">Publicado</span>
                <?php else: ?>
                    <span class="status-pill vermelho">Despublicado</span>
                <?php endif; ?>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('eventoDocumentos/editar/' . (int) $evento['id'] . '/' . (int) $documento['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $documento['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Baixar" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                </a>
                <a href="<?php echo url('eventoDocumentos/historico/' . (int) $evento['id'] . '/' . urlencode($documento['grupo_documento'])); ?>" class="btn-icone" title="Ver histórico de versões">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </a>
                <?php if ((int) $documento['publicado'] === 1): ?>
                    <form method="post" action="<?php echo url('eventoDocumentos/despublicar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?php echo (int) $documento['id']; ?>">
                        <button type="submit" class="btn-icone" title="Despublicar (some da página do evento, continua salvo)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?php echo url('eventoDocumentos/republicar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?php echo (int) $documento['id']; ?>">
                        <button type="submit" class="btn-icone" title="Republicar (volta a aparecer na página do evento)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?php echo url('eventoDocumentos/removerGrupo/' . (int) $evento['id']); ?>" onsubmit="return confirm('Remover TODAS as versões deste documento? Não pode ser desfeito.');"><?= campoCsrf() ?>
                    <input type="hidden" name="grupo_documento" value="<?php echo htmlspecialchars($documento['grupo_documento'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn-icone" title="Remover todas as versões">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        </svg>
                    </button>
                </form>
            </div>
            <div class="reordenar-botoes">
                <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($documentos) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
