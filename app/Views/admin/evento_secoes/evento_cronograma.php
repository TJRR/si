<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php include __DIR__ . '/_cabecalho_secao.php'; ?>

<p style="color:#555;font-size:0.9em;">Seção em duas colunas: à esquerda, a etiqueta, o título, o texto, até três botões e a linha de contato; à direita, um quadro branco com a linha do tempo dos marcos, na ordem em que aparecem. Use o texto do período como ele deve ser lido, por exemplo "19 a 26/10/2026" ou "16/10/2026, até 23h59".</p>

<form method="post" action="<?php echo url('eventoSecoes/editar/' . (int) $evento['id'] . '/cronograma/' . (int) $secao['id']); ?>"><?= campoCsrf() ?>
    <?php include __DIR__ . '/_campos_comuns.php'; ?>

    <label>Título do quadro da linha do tempo:
        <input type="text" name="titulo_quadro" maxlength="150" placeholder="Cronograma de submissão" value="<?php echo htmlspecialchars((string) (isset($secao['titulo_quadro']) ? $secao['titulo_quadro'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <fieldset>
        <legend>Botão principal (opcional)</legend>
        <p style="color:#555;font-size:0.9em;">Escolha um documento do evento (sub-aba Documentos) ou digite um destino. Com documento escolhido, o botão abre sempre a versão atual dele e some da página se o documento for despublicado.</p>
        <label>Texto do botão:
            <input type="text" name="botao1_titulo" maxlength="150" placeholder="Acessar o Edital completo" value="<?php echo htmlspecialchars((string) (isset($secao['botao1_titulo']) ? $secao['botao1_titulo'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Documento do evento:
            <select name="botao1_documento_id">
                <option value="">Nenhum (usar o destino digitado)</option>
                <?php foreach ($documentos as $documento): ?>
                    <option value="<?php echo (int) $documento['id']; ?>" <?php echo (int) (isset($secao['botao1_documento_id']) ? $secao['botao1_documento_id'] : 0) === (int) $documento['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?><?php echo (int) $documento['publicado'] === 1 ? '' : ' (despublicado)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Destino digitado:
            <input type="text" name="botao1_link" maxlength="255" value="<?php echo htmlspecialchars((string) (isset($secao['botao1_link']) ? $secao['botao1_link'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Botão secundário (opcional)</legend>
        <label>Texto do botão:
            <input type="text" name="botao2_titulo" maxlength="150" placeholder="Enviar meu trabalho" value="<?php echo htmlspecialchars((string) (isset($secao['botao2_titulo']) ? $secao['botao2_titulo'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Destino:
            <input type="text" name="botao2_link" maxlength="255" placeholder="trabalho/formulario/<?php echo (int) $evento['id']; ?>" value="<?php echo htmlspecialchars((string) (isset($secao['botao2_link']) ? $secao['botao2_link'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend>Terceiro botão (opcional)</legend>
        <p style="color:#555;font-size:0.9em;">Usado, por exemplo, para oferecer o modelo do resumo expandido para baixar. Funciona como o botão principal: escolha um documento do evento (sub-aba Documentos) ou digite um destino. O botão só aparece na página quando tem texto e um destino válido.</p>
        <label>Texto do botão:
            <input type="text" name="botao3_titulo" maxlength="150" placeholder="Baixar o modelo do resumo expandido" value="<?php echo htmlspecialchars((string) (isset($secao['botao3_titulo']) ? $secao['botao3_titulo'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Documento do evento:
            <select name="botao3_documento_id">
                <option value="">Nenhum (usar o destino digitado)</option>
                <?php foreach ($documentos as $documento): ?>
                    <option value="<?php echo (int) $documento['id']; ?>" <?php echo (int) (isset($secao['botao3_documento_id']) ? $secao['botao3_documento_id'] : 0) === (int) $documento['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?><?php echo (int) $documento['publicado'] === 1 ? '' : ' (despublicado)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Destino digitado:
            <input type="text" name="botao3_link" maxlength="255" value="<?php echo htmlspecialchars((string) (isset($secao['botao3_link']) ? $secao['botao3_link'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
    </fieldset>

    <p style="color:#555;font-size:0.9em;">Destinos aceitos nos três botões: âncora da própria página ("#programacao"), endereço completo ("https://...") ou endereço interno do sistema ("trabalho/formulario/<?php echo (int) $evento['id']; ?>").</p>

    <label><input type="checkbox" name="mostrar_contato" value="1" <?php echo !empty($secao['mostrar_contato']) ? 'checked' : ''; ?>> Mostrar e-mail e WhatsApp de contato abaixo dos botões (vêm de Configurações, aba Contato)</label>

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
