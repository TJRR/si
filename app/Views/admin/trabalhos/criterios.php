<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Critérios de avaliação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">A nota máxima de um trabalho é a soma da nota máxima de cada critério (hoje: <?php echo htmlspecialchars(number_format($notaMaximaTotal, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>).</p>

<?php if (empty($criterios)): ?>
    <p>Nenhum critério cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'trabalhos/criterioReordenar/' . (int) $evento['id']; ?>">
        <?php foreach ($criterios as $indice => $criterio): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $criterio['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <br>
                <span><?php echo htmlspecialchars((string) $criterio['descricao'], ENT_QUOTES, 'UTF-8'); ?> · Nota máxima <?php echo htmlspecialchars($criterio['nota_maxima'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('trabalhos/criterioRemover/' . (int) $criterio['id']); ?>" onsubmit="return confirm('Remover este critério?');"><?= campoCsrf() ?>
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
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($criterios) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Novo critério</h2>
<form method="post" action="<?php echo url('trabalhos/criterios/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="acao" value="criar_criterio">
    <label>Nome: <input type="text" name="nome" required maxlength="150"></label>
    <label>Descrição: <input type="text" name="descricao" maxlength="255"></label>
    <label>Nota máxima: <input type="number" step="0.01" name="nota_maxima" value="2.00" required></label>
    <button type="submit">Cadastrar critério</button>
</form>

<h2>Resumo para o avaliador</h2>
<p style="color:#555;font-size:0.9em;">Texto que o avaliador consulta dentro da tela de avaliação (nome de cada critério, faixa de nota e como interpretar) - não é o edital inteiro, só este resumo.</p>
<form method="post" action="<?php echo url('trabalhos/criterios/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="acao" value="salvar_resumo">
    <?php $nome = 'criterios_resumo_html'; $valor = $criteriosResumoHtml; $rotulo = null; ?>
    <?php include __DIR__ . '/../_editor_rico.php'; ?>
    <button type="submit">Salvar resumo</button>
</form>
