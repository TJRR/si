<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Tipos de atividade: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('atividades/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">O tipo aparece como etiqueta colorida nas seções "Destaques" e "Programação" da página pública. Cada evento tem os seus; nenhuma atividade é obrigada a ter tipo.</p>

<?php if (empty($tipos)): ?>
    <p>Nenhum tipo cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="<?php echo 'atividadeTipos/reordenar/' . (int) $evento['id']; ?>">
        <?php foreach ($tipos as $indice => $tipo): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $tipo['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <form method="post" action="<?php echo url('atividadeTipos/salvar/' . (int) $evento['id']); ?>" class="secao-linha-form"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $tipo['id']; ?>">
                    <label>Nome: <input type="text" name="nome" maxlength="60" required value="<?php echo htmlspecialchars($tipo['nome'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Cor: <input type="text" name="cor" maxlength="7" placeholder="#006699" value="<?php echo htmlspecialchars((string) $tipo['cor'], ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <button type="submit" class="btn-acao">Salvar</button>
                </form>
            </div>
            <div class="acoes-icones">
                <form method="post" action="<?php echo url('atividadeTipos/remover/' . (int) $evento['id']); ?>" onsubmit="return confirm('Remover este tipo?');"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $tipo['id']; ?>">
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
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($tipos) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Novo tipo</h2>
<form method="post" action="<?php echo url('atividadeTipos/index/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Nome: <input type="text" name="nome" maxlength="60" required placeholder="Oficina"></label>
    <label>Cor: <input type="text" name="cor" maxlength="7" placeholder="#006699"></label>
    <button type="submit">Cadastrar tipo</button>
</form>
