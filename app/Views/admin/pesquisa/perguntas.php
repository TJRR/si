<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Perguntas da pesquisa: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar): ?>
        <a href="<?php echo url('pesquisa/novaPergunta/' . (int) $evento['id']); ?>" class="btn-acao">+ Nova pergunta</a>
        <?php endif; ?>
    </div>
</div>

<p style="color:#555;">
    As perguntas aparecem para o participante na ordem definida aqui. Pergunta que já recebeu resposta não
    pode ter o enunciado, o tipo nem as opções alterados, porque a resposta guarda a posição da opção
    escolhida, e mexer na lista mudaria o significado do que já foi respondido.
</p>

<?php if (empty($perguntas)): ?>
    <p>Nenhuma pergunta cadastrada ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" <?php echo $podeEditar ? 'data-reordenar-rota="' . 'pesquisa/reordenarPerguntas/' . (int) $evento['id'] . '"' : ''; ?>>
        <?php foreach ($perguntas as $indice => $pergunta): ?>
        <?php $rotuloTipo = isset($tipos[$pergunta['tipo']]) ? $tipos[$pergunta['tipo']] : $pergunta['tipo']; ?>
        <li class="reordenar-item" <?php echo $podeEditar ? 'draggable="true" data-id="' . (int) $pergunta['id'] . '"' : ''; ?>>
            <?php if ($podeEditar): ?>
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <?php endif; ?>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($pergunta['enunciado'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if ((int) $pergunta['ativa'] !== 1): ?>
                    <span class="status-pill laranja">Desativada</span>
                <?php endif; ?>
                <?php if ((int) $pergunta['total_respostas'] > 0): ?>
                    <span class="status-pill verde">Já respondida</span>
                <?php endif; ?>
                <br>
                <span>
                    <?php echo htmlspecialchars($rotuloTipo, ENT_QUOTES, 'UTF-8'); ?>
                    · <?php echo $pergunta['obrigatoria'] ? 'Obrigatória' : 'Não obrigatória'; ?>
                </span>
            </div>
            <?php if ($podeEditar): ?>
            <div class="acoes-icones">
                <a href="<?php echo url('pesquisa/editarPergunta/' . (int) $evento['id'] . '/' . (int) $pergunta['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('pesquisa/removerPergunta/' . (int) $evento['id'] . '/' . (int) $pergunta['id']); ?>" style="display:inline;" onsubmit="return confirm('Remover esta pergunta? Se ela já tem respostas, será apenas desativada.');"><?= campoCsrf() ?>
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
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($perguntas) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
