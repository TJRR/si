<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Competições: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar): ?>
            <a href="<?php echo url('competicoes/novo/' . (int) $evento['id']); ?>" class="btn-acao">Nova competição</a>
        <?php endif; ?>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">
    Competições e experiências em que o participante pontua por participar (cantar no karaokê, competir na
    Batalha de Prompts). Não há inscrição prévia nem vencedor: quem participa lê, no aplicativo, o código que o
    responsável mostra na hora, e pontua uma vez por competição. O código fica num cartão para imprimir e, para
    quem é facilitador da atividade ligada à competição, em tela cheia em "Minhas facilitações". Quem só assiste
    pontua pela presença na atividade, pelo código afixado no espaço. O facilitador não pontua na competição que
    conduz.
</p>

<?php if (empty($competicoes)): ?>
    <p>Nenhuma competição cadastrada neste evento.</p>
<?php else: ?>
    <ul class="reordenar-lista" <?php echo $podeEditar ? 'data-reordenar-rota="competicoes/reordenar/' . (int) $evento['id'] . '"' : ''; ?>>
        <?php foreach ($competicoes as $indice => $competicao): ?>
            <li class="reordenar-item" <?php echo $podeEditar ? 'draggable="true" data-id="' . (int) $competicao['id'] . '"' : ''; ?>>
                <?php if ($podeEditar): ?>
                    <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
                <?php endif; ?>
                <div class="reordenar-conteudo">
                    <strong><?php echo htmlspecialchars($competicao['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ((int) $competicao['ativo'] !== 1): ?>
                        <span class="status-pill laranja">Desativada</span>
                    <?php endif; ?>
                    <br>
                    <span>
                        <?php echo (int) $competicao['pontos_participacao']; ?> <?php echo (int) $competicao['pontos_participacao'] === 1 ? 'ponto' : 'pontos'; ?> por participação
                        · código <span style="font-family:'Courier New',Courier,monospace;letter-spacing:2px;"><?php echo htmlspecialchars($competicao['codigo_participacao'], ENT_QUOTES, 'UTF-8'); ?></span>
                        · <?php echo (int) $competicao['total_participacoes']; ?> <?php echo (int) $competicao['total_participacoes'] === 1 ? 'participação válida' : 'participações válidas'; ?>
                    </span>
                    <br>
                    <small style="color:#555;">
                        <?php if (!empty($competicao['atividade_nome'])): ?>
                            Atividade: <?php echo htmlspecialchars($competicao['atividade_nome'], ENT_QUOTES, 'UTF-8'); ?>
                            (<?php echo htmlspecialchars(formatarDataHora($competicao['atividade_inicio']), ENT_QUOTES, 'UTF-8'); ?> a
                            <?php echo htmlspecialchars(formatarDataHora($competicao['atividade_fim']), ENT_QUOTES, 'UTF-8'); ?>): a leitura vale da abertura da leitura da atividade até o fim dela.
                        <?php else: ?>
                            Sem atividade ligada: a leitura vale nos dias do evento.
                        <?php endif; ?>
                    </small>
                    <br>
                    <a href="<?php echo url('competicoes/cartao/' . (int) $evento['id'] . '/' . (int) $competicao['id']); ?>" target="_blank" rel="noopener">Imprimir o cartão do código</a>
                </div>
                <?php if ($podeEditar): ?>
                    <div class="acoes-icones">
                        <a href="<?php echo url('competicoes/editar/' . (int) $evento['id'] . '/' . (int) $competicao['id']); ?>" class="btn-icone" title="Editar">✎</a>
                        <form method="post" action="<?php echo url('competicoes/remover/' . (int) $evento['id'] . '/' . (int) $competicao['id']); ?>" onsubmit="return confirm('Remover esta competição? Se ela já tiver participações, será desativada.');"><?= campoCsrf() ?>
                            <button type="submit" class="btn-icone" title="Remover">✕</button>
                        </form>
                    </div>
                    <div class="reordenar-botoes">
                        <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                        <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($competicoes) - 1 ? 'disabled' : ''; ?>>▼</button>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
