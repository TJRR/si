<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$rotulosFixos = [
    'trilhas' => 'Trilhas',
    'cronograma' => 'Cronograma',
    'temas' => 'Desafios',
    'faq' => 'Perguntas frequentes',
];
?>
<div class="pagina-titulo-acoes">
    <h1>Ordenação</h1>
</div>
<p>Define a ordem das seções que aparecem entre as Faixas e o rodapé da home, inclusive das seções fixas (Trilhas, Cronograma, Desafios, Perguntas frequentes), não só dos blocos de conteúdo. O Carrossel e as Faixas ficam sempre no topo; o Contato fica sempre no rodapé: nenhum dos dois entra nesta lista.</p>

<?php if (empty($secoes)): ?>
    <p>Nenhuma seção cadastrada ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="ordenacaoHome/reordenar">
        <?php foreach ($secoes as $indice => $secao): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $secao['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <?php if ($secao['tipo'] === 'fixa'): ?>
                    <strong><?php echo htmlspecialchars($rotulosFixos[$secao['chave_fixa']] ?? $secao['chave_fixa'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="status-pill">seção fixa</span>
                <?php else: ?>
                    <strong><?php echo htmlspecialchars((string) $secao['bloco_titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ($secao['bloco_chave'] === 'sobre' || $secao['bloco_chave'] === 'premiacao'): ?>
                        <span class="status-pill">bloco padrão</span>
                    <?php else: ?>
                        <span class="status-pill">bloco livre</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="reordenar-botoes">
                <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($secoes) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
