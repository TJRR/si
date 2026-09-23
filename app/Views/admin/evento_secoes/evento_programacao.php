<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Programação com uma aba por dia. No modo vinculado, a seção lê as Atividades do evento e agrupa por dia e turno sozinha, usando o tipo cadastrado como etiqueta.</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/programacao/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>
    <label>De onde vem a programação:
        <select name="fonte">
            <option value="atividades" <?php echo $secao['fonte'] === 'atividades' ? 'selected' : ''; ?>>Das Atividades cadastradas no evento</option>
            <option value="itens" <?php echo $secao['fonte'] === 'itens' ? 'selected' : ''; ?>>Dos itens cadastrados aqui</option>
        </select>
    </label>

    <label><input type="checkbox" name="mostrar_local" value="1" <?php echo (int) $secao['mostrar_local'] === 1 ? 'checked' : ''; ?>> Mostrar o local de cada item</label>

    <button type="submit">Salvar seção</button>
</form>

<h2>Itens digitados</h2>

<?php if (empty($itens)): ?>
    <p>Nenhum item digitado (só faz falta no modo digitado).</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoSecoes/itemReordenar/' . (int) $evento['id'] . '/programacao/' . (int) $secao['id']; ?>">
        <?php foreach ($itens as $indice => $item): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $item['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <form method="post" action="<?php echo url('eventoSecoes/itemSalvar/' . (int) $evento['id'] . '/programacao/' . (int) $secao['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                    <label>Atividade: <select name="atividade_id"><option value="">Sem vínculo (texto próprio)</option><?php foreach ($atividades as $opcao): ?><option value="<?php echo (int) $opcao['id']; ?>" <?php echo (int) $item['atividade_id'] === (int) $opcao['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
                    <label>Dia: <input type="date" name="dia" value="<?php echo htmlspecialchars((string) $item['dia'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Turno: <select name="turno"><option value="manha" <?php echo $item['turno'] === 'manha' ? 'selected' : ''; ?>>Manhã</option><option value="tarde" <?php echo $item['turno'] === 'tarde' ? 'selected' : ''; ?>>Tarde</option><option value="noite" <?php echo $item['turno'] === 'noite' ? 'selected' : ''; ?>>Noite</option></select></label>
                    <label>Horário: <input type="text" name="horario_texto" maxlength="40" value="<?php echo htmlspecialchars((string) $item['horario_texto'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Tipo: <input type="text" name="tipo_texto" maxlength="60" value="<?php echo htmlspecialchars((string) $item['tipo_texto'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Título: <input type="text" name="titulo" maxlength="255" value="<?php echo htmlspecialchars((string) $item['titulo'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Local: <input type="text" name="local" maxlength="150" value="<?php echo htmlspecialchars((string) $item['local'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Descrição: <input type="text" name="descricao" maxlength="255" value="<?php echo htmlspecialchars((string) $item['descricao'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('eventoSecoes/itemRemover/' . (int) $evento['id'] . '/programacao/' . (int) $secao['id']); ?>" onsubmit="return confirm('Remover este item?');"><?= campoCsrf() ?>
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

<h3>Novo item da programação</h3>
<form method="post" action="<?php echo url('eventoSecoes/itemNovo/' . (int) $evento['id'] . '/programacao/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <label>Atividade: <select name="atividade_id"><option value="">Sem vínculo (texto próprio)</option><?php foreach ($atividades as $opcao): ?><option value="<?php echo (int) $opcao['id']; ?>" ><?php echo htmlspecialchars($opcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
    <label>Dia: <input type="date" name="dia"></label>
    <label>Turno: <select name="turno"><option value="manha" >Manhã</option><option value="tarde" >Tarde</option><option value="noite" >Noite</option></select></label>
    <label>Horário: <input type="text" name="horario_texto" maxlength="40" placeholder="08h30 às 12h30"></label>
    <label>Tipo: <input type="text" name="tipo_texto" maxlength="60" placeholder="Oficina"></label>
    <label>Título: <input type="text" name="titulo" maxlength="255"></label>
    <label>Local: <input type="text" name="local" maxlength="150"></label>
    <label>Descrição: <input type="text" name="descricao" maxlength="255"></label>
    <button type="submit">Adicionar</button>
</form>
