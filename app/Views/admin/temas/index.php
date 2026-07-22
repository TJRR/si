<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Temas de <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>
<?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
<p><a href="<?php echo url('temas/novo/' . (int) $trilha['id']); ?>">+ Novo tema</a></p>
<?php endif; ?>

<?php if (empty($temas)): ?>
    <p>Nenhum tema cadastrado.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="temas/reordenar/<?php echo (int) $trilha['id']; ?>">
        <?php foreach ($temas as $indice => $tema): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $tema['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <a href="<?php echo url('temas/desafios/' . (int) $tema['id']); ?>"><strong><?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></a>
                <br>
                <span class="status-pill <?php echo $tema['ativo'] ? 'verde' : 'vermelho'; ?>"><?php echo $tema['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('temas/desafios/' . (int) $tema['id']); ?>" class="btn-icone" title="Ver desafios">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 6h16"></path>
                        <path d="M4 12h16"></path>
                        <path d="M4 18h16"></path>
                    </svg>
                </a>
                <a href="<?php echo url('temas/editar/' . (int) $tema['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                <form method="post" action="<?php echo url('temas/remover'); ?>" onsubmit="return confirm('Remover este tema? Só funciona se ele não tiver desafios cadastrados.');">
                    <input type="hidden" name="id" value="<?php echo (int) $tema['id']; ?>">
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
                <?php endif; ?>
            </div>
            <div class="reordenar-botoes">
                <button type="button" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($temas) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
