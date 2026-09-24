<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Cartões curtos com o que a organização quer evidenciar. No modo vinculado, a seção lista as Atividades marcadas com "destacar na página"; no modo digitado, usa os itens abaixo.</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/destaques/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>
    <label>De onde vêm os destaques:
        <select name="fonte">
            <option value="atividades" <?php echo $secao['fonte'] === 'atividades' ? 'selected' : ''; ?>>Das Atividades marcadas para destacar</option>
            <option value="itens" <?php echo $secao['fonte'] === 'itens' ? 'selected' : ''; ?>>Dos itens cadastrados aqui</option>
        </select>
    </label>

    <label>Cartões por linha:
        <input type="number" name="colunas" min="1" max="6" value="<?php echo (int) $secao['colunas']; ?>">
    </label>

    <button type="submit">Salvar seção</button>
</form>

<h2>Itens digitados</h2>

<?php if (empty($itens)): ?>
    <p>Nenhum item digitado (só faz falta no modo digitado).</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoSecoes/itemReordenar/' . (int) $evento['id'] . '/destaques/' . (int) $secao['id']; ?>">
        <?php foreach ($itens as $indice => $item): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $item['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <form method="post" action="<?php echo url('eventoSecoes/itemSalvar/' . (int) $evento['id'] . '/destaques/' . (int) $secao['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                    <label>Atividade: <select name="atividade_id"><option value="">Sem vínculo (texto próprio)</option><?php foreach ($atividades as $opcao): ?><option value="<?php echo (int) $opcao['id']; ?>" <?php echo (int) $item['atividade_id'] === (int) $opcao['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
                    <label>Título: <input type="text" name="titulo" maxlength="150" value="<?php echo htmlspecialchars((string) $item['titulo'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Quando: <input type="text" name="quando_texto" maxlength="120" value="<?php echo htmlspecialchars((string) $item['quando_texto'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Local: <input type="text" name="local" maxlength="150" value="<?php echo htmlspecialchars((string) $item['local'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Descrição: <input type="text" name="descricao" maxlength="255" value="<?php echo htmlspecialchars((string) $item['descricao'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Ícone: <select name="icone"><option value="">Sem ícone</option><?php foreach (\App\Repositories\EventoSecaoDestaquesRepository::ICONES as $chaveIcone => $icone): ?><option value="<?php echo $chaveIcone; ?>" <?php echo (string) $item['icone'] === $chaveIcone ? 'selected' : ''; ?>><?php echo htmlspecialchars($icone['rotulo'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
                    <label>Cor do quadrado do ícone: <input type="text" name="icone_cor" maxlength="7" value="<?php echo htmlspecialchars((string) (isset($item['icone_cor']) ? $item['icone_cor'] : ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('eventoSecoes/itemRemover/' . (int) $evento['id'] . '/destaques/' . (int) $secao['id']); ?>" onsubmit="return confirm('Remover este item?');"><?= campoCsrf() ?>
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

<h3>Novo destaque</h3>
<form method="post" action="<?php echo url('eventoSecoes/itemNovo/' . (int) $evento['id'] . '/destaques/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <label>Atividade: <select name="atividade_id"><option value="">Sem vínculo (texto próprio)</option><?php foreach ($atividades as $opcao): ?><option value="<?php echo (int) $opcao['id']; ?>" ><?php echo htmlspecialchars($opcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
    <label>Título: <input type="text" name="titulo" maxlength="150"></label>
    <label>Quando: <input type="text" name="quando_texto" maxlength="120" placeholder="05/11 · 10h30 às 12h30"></label>
    <label>Local: <input type="text" name="local" maxlength="150"></label>
    <label>Descrição: <input type="text" name="descricao" maxlength="255"></label>
    <label>Ícone: <select name="icone"><option value="">Sem ícone</option><?php foreach (\App\Repositories\EventoSecaoDestaquesRepository::ICONES as $chaveIcone => $icone): ?><option value="<?php echo $chaveIcone; ?>"><?php echo htmlspecialchars($icone['rotulo'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
    <label>Cor do quadrado do ícone: <input type="text" name="icone_cor" maxlength="7" placeholder="#ea5a43"></label>
    <button type="submit">Adicionar</button>
</form>
