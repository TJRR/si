<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$ativas = array_values(array_filter($faqs, function ($faq) {
    return (int) $faq['ativo_na_edicao'] === 1;
}));
$disponiveis = array_values(array_filter($faqs, function ($faq) {
    return (int) $faq['ativo_na_edicao'] !== 1;
}));
?>
<div class="pagina-titulo-acoes">
    <h1>FAQ de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('faq/novo'); ?>" class="btn-acao">+ Nova pergunta no banco</a>
    </div>
</div>
<p>Ative perguntas do banco global para esta edição e arraste para definir a ordem de exibição no acordeão da home.</p>

<h2>Ativas nesta edição</h2>
<?php if (empty($ativas)): ?>
    <p>Nenhuma pergunta ativa ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="faqConcurso/reordenar/<?php echo (int) $concurso['id']; ?>">
        <?php foreach ($ativas as $indice => $faq): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $faq['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($faq['pergunta'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($faq['categoria'])): ?>
                    <span class="status-pill"><?php echo htmlspecialchars($faq['categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>
            <form method="post" action="<?php echo url('faqConcurso/alternar/' . (int) $concurso['id'] . '/' . (int) $faq['id']); ?>">
                <input type="hidden" name="ativo" value="0">
                <button type="submit" class="btn-icone" title="Desativar nesta edição">✕</button>
            </form>
            <div class="reordenar-botoes">
                <button type="button" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($ativas) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Disponíveis no banco (não ativas nesta edição)</h2>
<?php if (empty($disponiveis)): ?>
    <p>Todas as perguntas do banco já estão ativas nesta edição.</p>
<?php else: ?>
    <ul class="reordenar-lista">
        <?php foreach ($disponiveis as $faq): ?>
        <li class="reordenar-item">
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($faq['pergunta'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($faq['categoria'])): ?>
                    <span class="status-pill"><?php echo htmlspecialchars($faq['categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>
            <form method="post" action="<?php echo url('faqConcurso/alternar/' . (int) $concurso['id'] . '/' . (int) $faq['id']); ?>">
                <input type="hidden" name="ativo" value="1">
                <button type="submit" class="btn-acao">Ativar nesta edição</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
