<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Perguntas e respostas em acordeão, iguais às do Concurso, mas com cadastro próprio deste evento.</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/faq/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>

    <button type="submit">Salvar seção</button>
</form>

<h2>Perguntas</h2>

<?php if (empty($itens)): ?>
    <p>Nenhuma pergunta cadastrada ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoSecoes/itemReordenar/' . (int) $evento['id'] . '/faq/' . (int) $secao['id']; ?>">
        <?php foreach ($itens as $indice => $item): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $item['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <form method="post" action="<?php echo url('eventoSecoes/itemSalvar/' . (int) $evento['id'] . '/faq/' . (int) $secao['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                    <label>Pergunta: <input type="text" name="pergunta" maxlength="255" required value="<?php echo htmlspecialchars((string) $item['pergunta'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Resposta: <textarea name="resposta_html" rows="3"><?php echo htmlspecialchars((string) $item['resposta_html'], ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                    <label><input type="checkbox" name="ativo" value="1" <?php echo (int) $item['ativo'] === 1 ? 'checked' : ''; ?>> Ativa</label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('eventoSecoes/itemRemover/' . (int) $evento['id'] . '/faq/' . (int) $secao['id']); ?>" onsubmit="return confirm('Remover este item?');"><?= campoCsrf() ?>
                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
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
                <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($itens) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h3>Nova pergunta</h3>
<form method="post" action="<?php echo url('eventoSecoes/itemNovo/' . (int) $evento['id'] . '/faq/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <label>Pergunta: <input type="text" name="pergunta" maxlength="255" required></label>
    <label>Resposta: <textarea name="resposta_html" rows="3"></textarea></label>
    <label><input type="checkbox" name="ativo" value="1" checked> Ativa</label>
    <button type="submit">Adicionar</button>
</form>
