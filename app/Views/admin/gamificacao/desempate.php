<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $podeMudar = $podeEditar && empty($gincanaEncerrada); ?>
<div class="pagina-titulo-acoes">
    <h1>Desempate: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera a cascata.</p>
<?php elseif (!empty($gincanaEncerrada)): ?>
    <p class="status-pill laranja">A gincana foi encerrada: a cascata ficou travada, porque mudá-la mudaria a ordem final da classificação.</p>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">
    Quando duas pessoas têm o mesmo total, a classificação aplica os critérios abaixo, na ordem, até um deles
    desempatar. Cada critério tem a sua direção fixa (por exemplo, "mais pontos de presença" vence quem tem mais).
    Esgotados os critérios, as pessoas dividem a posição, e a lista da aba Classificação marca o empate.
    "Inscrição mais antiga" sempre desempata, porque duas inscrições nunca têm o mesmo momento e número; use-o por
    último se a organização precisar de uma ordem sem empate, por exemplo para entregar prêmio aos primeiros.
</p>

<?php if (empty($escolhidos)): ?>
    <p>Nenhum critério escolhido: quem empata no total divide a posição.</p>
<?php else: ?>
    <ul class="reordenar-lista" <?php echo $podeMudar ? 'data-reordenar-rota="gamificacao/desempateReordenar/' . (int) $evento['id'] . '"' : ''; ?>>
        <?php foreach ($escolhidos as $indice => $linha): ?>
            <li class="reordenar-item" <?php echo $podeMudar ? 'draggable="true" data-id="' . (int) $linha['id'] . '"' : ''; ?>>
                <?php if ($podeMudar): ?>
                    <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
                <?php endif; ?>
                <div class="reordenar-conteudo">
                    <?php echo htmlspecialchars(\App\Services\GamificacaoService::rotuloDoCriterio($linha['criterio']), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php if ($podeMudar): ?>
                    <div class="acoes-icones">
                        <form method="post" action="<?php echo url('gamificacao/desempateRemover/' . (int) $evento['id']); ?>" onsubmit="return confirm('Retirar este critério da cascata?');"><?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?php echo (int) $linha['id']; ?>">
                            <button type="submit" class="btn-icone" title="Retirar">✕</button>
                        </form>
                    </div>
                    <div class="reordenar-botoes">
                        <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                        <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($escolhidos) - 1 ? 'disabled' : ''; ?>>▼</button>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($podeMudar && !empty($disponiveis)): ?>
    <h2>Acrescentar critério</h2>
    <form method="post" action="<?php echo url('gamificacao/desempateAdicionar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
        <label>Critério
            <select name="criterio" required>
                <?php foreach ($disponiveis as $chave => $rotulo): ?>
                    <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Acrescentar ao fim da cascata</button>
    </form>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">
    "Quem chegou primeiro" compara o instante do último ponto válido de cada pessoa. Um crédito criado por
    reconferência leva o instante da reconferência: por isso, reconfira antes do evento, e não durante.
</p>
