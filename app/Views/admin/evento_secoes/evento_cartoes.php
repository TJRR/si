<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Cartões coloridos com resumo curto, que abrem o texto completo ao clique. Cada cartão pode apontar para um eixo temático já cadastrado, e aí o texto completo vem de lá, sem precisar repetir o texto oficial do edital.</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/cartoes/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>
    <label>Cartões por linha:
        <input type="number" name="colunas" min="1" max="6" value="<?php echo (int) $secao['colunas']; ?>">
    </label>

    <label>Efeito ao passar o mouse:
        <select name="efeito_hover">
            <?php foreach (['nenhum' => 'Nenhum', 'elevar' => 'Elevar', 'escala' => 'Aumentar', 'borda' => 'Destacar borda'] as $chave => $rotuloOpcao): ?>
                <option value="<?php echo $chave; ?>" <?php echo $secao['efeito_hover'] === $chave ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Efeito ao abrir o cartão:
        <select name="efeito_abrir">
            <?php foreach (['nenhum' => 'Nenhum', 'deslizar' => 'Deslizar', 'desvanecer' => 'Aparecer aos poucos'] as $chave => $rotuloOpcao): ?>
                <option value="<?php echo $chave; ?>" <?php echo $secao['efeito_abrir'] === $chave ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Efeito ao fechar o cartão:
        <select name="efeito_fechar">
            <?php foreach (['nenhum' => 'Nenhum', 'deslizar' => 'Deslizar', 'desvanecer' => 'Sumir aos poucos'] as $chave => $rotuloOpcao): ?>
                <option value="<?php echo $chave; ?>" <?php echo $secao['efeito_fechar'] === $chave ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <button type="submit">Salvar seção</button>
</form>

<h2>Cartões</h2>

<?php if (empty($itens)): ?>
    <p>Nenhum cartão cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoSecoes/itemReordenar/' . (int) $evento['id'] . '/cartoes/' . (int) $secao['id']; ?>">
        <?php foreach ($itens as $indice => $item): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $item['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <form method="post" action="<?php echo url('eventoSecoes/itemSalvar/' . (int) $evento['id'] . '/cartoes/' . (int) $secao['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                    <label>Eixo temático: <select name="eixo_tematico_id"><option value="">Sem vínculo (texto próprio)</option><?php foreach ($eixos as $opcao): ?><option value="<?php echo (int) $opcao['id']; ?>" <?php echo (int) $item['eixo_tematico_id'] === (int) $opcao['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
                    <label>Etiqueta: <input type="text" name="etiqueta" maxlength="40" value="<?php echo htmlspecialchars((string) $item['etiqueta'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Título (vazio usa o nome do eixo): <input type="text" name="titulo" maxlength="150" value="<?php echo htmlspecialchars((string) $item['titulo'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Resumo curto: <input type="text" name="resumo" maxlength="255" value="<?php echo htmlspecialchars((string) $item['resumo'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Texto completo (vazio usa a descrição do eixo): <textarea name="detalhe_html" rows="3"><?php echo htmlspecialchars((string) $item['detalhe_html'], ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                    <label>Cor: <input type="text" name="cor" maxlength="7" value="<?php echo htmlspecialchars((string) $item['cor'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('eventoSecoes/itemRemover/' . (int) $evento['id'] . '/cartoes/' . (int) $secao['id']); ?>" onsubmit="return confirm('Remover este item?');"><?= campoCsrf() ?>
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

<h3>Novo cartão</h3>
<form method="post" action="<?php echo url('eventoSecoes/itemNovo/' . (int) $evento['id'] . '/cartoes/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <label>Eixo temático: <select name="eixo_tematico_id"><option value="">Sem vínculo (texto próprio)</option><?php foreach ($eixos as $opcao): ?><option value="<?php echo (int) $opcao['id']; ?>" ><?php echo htmlspecialchars($opcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
    <label>Etiqueta: <input type="text" name="etiqueta" maxlength="40" placeholder="EIXO 1"></label>
    <label>Título: <input type="text" name="titulo" maxlength="150"></label>
    <label>Resumo curto: <input type="text" name="resumo" maxlength="255"></label>
    <label>Texto completo: <textarea name="detalhe_html" rows="3"></textarea></label>
    <label>Cor: <input type="text" name="cor" maxlength="7" placeholder="#cbd744"></label>
    <button type="submit">Adicionar</button>
</form>
