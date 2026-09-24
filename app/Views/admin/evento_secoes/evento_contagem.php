<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Mostra quanto falta para a data escolhida e, ao lado, as datas que você quiser destacar. Sem data informada, a contagem usa a data de início do evento. Em cada data em destaque, o texto aparece como foi digitado; a data, quando informada, aparece depois dele.</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/contagem/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>

    <label>Data e hora alvo da contagem:
        <input type="datetime-local" name="data_alvo" value="<?php echo htmlspecialchars($secao['data_alvo'] !== null ? str_replace(' ', 'T', substr($secao['data_alvo'], 0, 16)) : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <label>Cor do círculo da contagem:
        <input type="text" name="cor_circulo" maxlength="7" placeholder="#141413" value="<?php echo htmlspecialchars((string) $secao['cor_circulo'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <p style="color:#555;font-size:0.9em;">Três anéis pulsam em volta do círculo, um de cada cor, em sequência. Em branco, valem laranja, azul e verde.</p>
    <?php foreach ([1 => '#ea5a43', 2 => '#006699', 3 => '#cbd744'] as $numeroAnel => $corSugerida): ?>
        <label>Cor do anel <?php echo $numeroAnel; ?>:
            <input type="text" name="cor_anel_<?php echo $numeroAnel; ?>" maxlength="7" placeholder="<?php echo $corSugerida; ?>" value="<?php echo htmlspecialchars((string) (isset($secao['cor_anel_' . $numeroAnel]) ? $secao['cor_anel_' . $numeroAnel] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    <?php endforeach; ?>

    <button type="submit">Salvar seção</button>
</form>

<h2>Datas em destaque</h2>

<?php if (empty($itens)): ?>
    <p>Nenhuma data em destaque ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'eventoSecoes/itemReordenar/' . (int) $evento['id'] . '/contagem/' . (int) $secao['id']; ?>">
        <?php foreach ($itens as $indice => $item): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $item['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <form method="post" action="<?php echo url('eventoSecoes/itemSalvar/' . (int) $evento['id'] . '/contagem/' . (int) $secao['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                    <label>Texto: <input type="text" name="texto" maxlength="150" required value="<?php echo htmlspecialchars($item['texto'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Data: <input type="date" name="data_referencia" value="<?php echo htmlspecialchars((string) $item['data_referencia'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Cor do marcador: <input type="text" name="cor_marcador" maxlength="7" value="<?php echo htmlspecialchars((string) $item['cor_marcador'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('eventoSecoes/itemRemover/' . (int) $evento['id'] . '/contagem/' . (int) $secao['id']); ?>" onsubmit="return confirm('Remover esta data?');"><?= campoCsrf() ?>
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

<h3>Nova data em destaque</h3>
<form method="post" action="<?php echo url('eventoSecoes/itemNovo/' . (int) $evento['id'] . '/contagem/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <label>Texto: <input type="text" name="texto" maxlength="150" required></label>
    <label>Data: <input type="date" name="data_referencia"></label>
    <label>Cor do marcador: <input type="text" name="cor_marcador" maxlength="7" placeholder="#ea5a43"></label>
    <button type="submit">Adicionar data</button>
</form>
