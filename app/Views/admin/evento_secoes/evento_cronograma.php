<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Linha do tempo de marcos, na ordem em que aparecem. Use o texto do período como ele deve ser lido, por exemplo "19 a 26/10/2026" ou "16/10/2026, até 23h59".</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/cronograma/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>

    <button type="submit">Salvar seção</button>
</form>

<h2>Marcos</h2>

<?php if (empty($itens)): ?>
    <p>Nenhum marco cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoSecoes/itemReordenar/' . (int) $evento['id'] . '/cronograma/' . (int) $secao['id']; ?>">
        <?php foreach ($itens as $indice => $item): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $item['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <form method="post" action="<?php echo url('eventoSecoes/itemSalvar/' . (int) $evento['id'] . '/cronograma/' . (int) $secao['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                    <label>Período: <input type="text" name="periodo_texto" maxlength="80" required value="<?php echo htmlspecialchars((string) $item['periodo_texto'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Descrição: <input type="text" name="descricao" maxlength="255" required value="<?php echo htmlspecialchars((string) $item['descricao'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Data de referência: <input type="date" name="data_referencia" value="<?php echo htmlspecialchars((string) $item['data_referencia'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Cor: <input type="text" name="cor" maxlength="7" value="<?php echo htmlspecialchars((string) $item['cor'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('eventoSecoes/itemRemover/' . (int) $evento['id'] . '/cronograma/' . (int) $secao['id']); ?>" onsubmit="return confirm('Remover este item?');"><?= campoCsrf() ?>
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

<h3>Novo marco</h3>
<form method="post" action="<?php echo url('eventoSecoes/itemNovo/' . (int) $evento['id'] . '/cronograma/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <label>Período: <input type="text" name="periodo_texto" maxlength="80" required placeholder="16/10/2026, até 23h59"></label>
    <label>Descrição: <input type="text" name="descricao" maxlength="255" required></label>
    <label>Data de referência: <input type="date" name="data_referencia"></label>
    <label>Cor: <input type="text" name="cor" maxlength="7" placeholder="#ea5a43"></label>
    <button type="submit">Adicionar</button>
</form>
