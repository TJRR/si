<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Declarações: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Cada declaração vira uma caixa de marcação no fim do formulário de submissão, na ordem desta lista. As obrigatórias precisam ser marcadas para o trabalho ser enviado. O texto aceito fica guardado junto com a submissão, exatamente como estava no dia do envio.</p>

<?php if (empty($termos)): ?>
    <p>Nenhuma declaração cadastrada ainda. Sem declaração cadastrada, o formulário de submissão não pede nenhum aceite.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'trabalhos/termoReordenar/' . (int) $evento['id']; ?>">
        <?php foreach ($termos as $indice => $termo): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $termo['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($termo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <br>
                <span>
                    <?php echo (int) $termo['obrigatorio'] === 1 ? 'Obrigatória' : 'Opcional'; ?>
                    ·
                    <?php echo (int) $termo['ativo'] === 1 ? 'Ativa' : 'Inativa'; ?>
                </span>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('trabalhos/termoEditar/' . (int) $termo['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('trabalhos/termoRemover/' . (int) $termo['id']); ?>" onsubmit="return confirm('Remover esta declaração?');"><?= campoCsrf() ?>
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
            <div class="reordenar-botoes">
                <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($termos) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Nova declaração</h2>
<form method="post" action="<?php echo url('trabalhos/termos/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Identificação interna (aparece na lista e nas mensagens de erro): <input type="text" name="rotulo" required maxlength="150"></label>

    <fieldset>
        <legend>Texto que a pessoa lê e aceita</legend>
        <?php
        $nome = 'texto_html';
        $valor = '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>

    <label><input type="checkbox" name="obrigatorio" value="1" checked> Aceite obrigatório para enviar o trabalho</label>
    <label><input type="checkbox" name="ativo" value="1" checked> Ativa (aparece no formulário)</label>

    <button type="submit">Cadastrar declaração</button>
</form>
