<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Regras de desempate: <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>

<p>Ordem de aplicação em caso de empate na Nota Final (1ª linha de cada etapa tem prioridade). As regras são configuradas por etapa: o desempate de uma etapa nunca usa critérios de outra.</p>

<?php if (empty($etapas)): ?>
    <p>Esta trilha ainda não tem etapas cadastradas.</p>
<?php else: ?>
    <?php foreach ($etapas as $etapa): ?>
        <?php $regrasDaEtapa = array_values(array_filter($regras, function ($regra) use ($etapa) {
            return (int) $regra['etapa_id'] === (int) $etapa['id'];
        })); ?>
        <h3><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <p><a href="<?php echo url('desempate/novo/' . (int) $trilha['id'] . '/' . (int) $etapa['id']); ?>">+ Nova regra de desempate nesta etapa</a></p>

        <?php if (empty($regrasDaEtapa)): ?>
            <p>Nenhuma regra de desempate cadastrada nesta etapa.</p>
        <?php else: ?>
            <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'desempate/reordenar/' . (int) $etapa['id']; ?>">
                <?php foreach ($regrasDaEtapa as $indice => $regra): ?>
                <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $regra['id']; ?>">
                    <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
                    <div class="reordenar-conteudo">
                        <strong><?php echo $regra['tipo'] === 'data_submissao' ? 'Data de inscrição (quem enviou primeiro)' : htmlspecialchars($regra['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <br>
                        <span><?php echo $regra['direcao'] === 'asc' ? 'Crescente (menor valor vence)' : 'Decrescente (maior valor vence)'; ?></span>
                    </div>
                    <div class="acoes-icones">
                        <form method="post" action="<?php echo url('desempate/remover'); ?>"><?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                            <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
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
                        <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($regrasDaEtapa) - 1 ? 'disabled' : ''; ?>>▼</button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
