<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Premiação de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php if ($concurso['modo_premiacao'] === 'geral'): ?>
        <div class="pagina-titulo-botoes">
            <a href="<?php echo url('premios/novo/' . (int) $concurso['id']); ?>" class="btn-acao">+ Novo prêmio</a>
        </div>
    <?php endif; ?>
</div>
<p>Regras gerais de premiação em texto rico ficam na tela de <a href="<?php echo url('blocos/index'); ?>">Blocos de conteúdo</a> (bloco padrão "Premiação").</p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('premios/definirModo/' . (int) $concurso['id']); ?>" class="admin-card" style="margin-bottom:1.5rem;"><?= campoCsrf() ?>
    <p><strong>Modo de premiação:</strong></p>
    <label>
        <input type="radio" name="modo_premiacao" value="geral" <?php echo $concurso['modo_premiacao'] === 'geral' ? 'checked' : ''; ?> onchange="this.form.submit()">
        Prêmio geral do concurso (1 lista única)
    </label><br>
    <label>
        <input type="radio" name="modo_premiacao" value="por_trilha" <?php echo $concurso['modo_premiacao'] === 'por_trilha' ? 'checked' : ''; ?> onchange="this.form.submit()">
        Prêmio por trilha (1 lista de 1º/2º/3º lugar para cada trilha)
    </label>
    <p><small>Trocar o modo não apaga prêmios já cadastrados, só muda a lista exibida aqui e na página pública.</small></p>
</form>

<?php
$renderizarLista = function (array $premios, $concursoId) {
    if (empty($premios)) {
        echo '<p>Nenhum prêmio cadastrado ainda.</p>';
        return;
    }
    ?>
    <ul class="reordenar-lista" data-reordenar-rota="premios/reordenar/<?php echo (int) $concursoId; ?>">
        <?php foreach ($premios as $indice => $premio): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $premio['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <?php if (!empty($premio['imagem_path'])): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $premio['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:4px;">
            <?php endif; ?>
            <div class="reordenar-conteudo">
                <strong><?php echo (int) $premio['posicao']; ?>º lugar</strong> — <?php echo htmlspecialchars($premio['descricao'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('premios/editar/' . (int) $premio['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('premios/remover'); ?>" onsubmit="return confirm('Remover este prêmio?');"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $premio['id']; ?>">
                    <input type="hidden" name="concurso_id" value="<?php echo (int) $concursoId; ?>">
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
                <button type="button" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($premios) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php
};
?>

<?php if ($concurso['modo_premiacao'] === 'por_trilha'): ?>
    <?php foreach ($grupos as $grupo): ?>
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="pagina-titulo-acoes">
                <h2><?php echo htmlspecialchars($grupo['trilha']['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="pagina-titulo-botoes">
                    <a href="<?php echo url('premios/novo/' . (int) $concurso['id'] . '/' . (int) $grupo['trilha']['id']); ?>" class="btn-acao">+ Novo prêmio</a>
                </div>
            </div>
            <?php $renderizarLista($grupo['premios'], $concurso['id']); ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <?php $renderizarLista($premios, $concurso['id']); ?>
<?php endif; ?>
