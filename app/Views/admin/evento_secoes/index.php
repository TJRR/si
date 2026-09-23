<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Seções da página: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Esta lista é a página pública do evento, de cima para baixo. Arraste para mudar a ordem, desmarque "Na página" para esconder uma seção sem apagá-la, e marque "No menu" para a seção virar item do menu do cabeçalho.</p>

<?php if (empty($secoes)): ?>
    <p>Nenhuma seção ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoSecoes/reordenar/' . (int) $evento['id']; ?>">
        <?php foreach ($secoes as $indice => $secao): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $secao['secao_id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <strong>
                    <?php echo htmlspecialchars(\App\Controllers\EventoSecaoAdminController::rotuloDoTipo($secao['tipo']), ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (!empty($secao['titulo_item'])): ?>
                        · <?php echo htmlspecialchars($secao['titulo_item'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </strong>
                <br>
                <form method="post" action="<?php echo url('eventoSecoes/salvarItem/' . (int) $evento['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="secao_id" value="<?php echo (int) $secao['secao_id']; ?>">
                    <label><input type="checkbox" name="ativo" value="1" <?php echo (int) $secao['ativo'] === 1 ? 'checked' : ''; ?>> Na página</label>
                    <label><input type="checkbox" name="mostrar_no_menu" value="1" <?php echo (int) $secao['mostrar_no_menu'] === 1 ? 'checked' : ''; ?>> No menu</label>
                    <label>Rótulo no menu: <input type="text" name="rotulo_menu" maxlength="60" value="<?php echo htmlspecialchars((string) $secao['rotulo_menu'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <?php if (isset($componentes[$secao['tipo']])): ?>
                    <a href="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/' . $secao['tipo'] . '/' . (int) $secao['referencia_id']); ?>" class="btn-icone" title="Editar conteúdo">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form method="post" action="<?php echo url('eventoSecoes/remover/' . (int) $evento['id'] . '/' . $secao['tipo'] . '/' . (int) $secao['referencia_id']); ?>" onsubmit="return confirm('Remover esta seção da página? O conteúdo dela é apagado junto.');"><?= campoCsrf() ?>
                        <button type="submit" class="btn-icone" title="Remover seção">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                            </svg>
                        </button>
                    </form>
                <?php elseif ($secao['tipo'] === 'bloco'): ?>
                    <a href="<?php echo url('eventoBlocos/editar/' . (int) $secao['referencia_id']); ?>" class="btn-icone" title="Editar bloco">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                <?php elseif ($secao['tipo'] === 'quadros'): ?>
                    <a href="<?php echo url('eventoSlides/index/' . (int) $evento['id']); ?>" class="btn-icone" title="Abrir Quadros de apresentação">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="3" width="20" height="14" rx="2"></rect>
                            <path d="M8 21h8"></path>
                            <path d="M12 17v4"></path>
                        </svg>
                    </a>
                <?php elseif ($secao['tipo'] === 'faixas'): ?>
                    <a href="<?php echo url('eventoBanners/index/' . (int) $evento['id']); ?>" class="btn-icone" title="Abrir Faixas">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="7" width="20" height="10" rx="2"></rect>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
            <div class="reordenar-botoes">
                <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($secoes) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Adicionar seção</h2>
<p style="color:#555;font-size:0.9em;">Cada tipo pode ser adicionado quantas vezes você quiser: a página aceita, por exemplo, dois conjuntos de cartões em pontos diferentes.</p>
<form method="post" action="<?php echo url('eventoSecoes/adicionar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Tipo:
        <select name="tipo" required>
            <?php foreach ($componentes as $chave => $componente): ?>
                <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($componente['rotulo'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Adicionar à página</button>
</form>
