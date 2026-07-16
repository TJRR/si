<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Desafios de <?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('temas/index/' . (int) $trilha['id']); ?>">Voltar aos temas</a></p>

<?php if (!empty($tema['descricao_longa'])): ?>
    <p><em><?php echo nl2br(htmlspecialchars($tema['descricao_longa'], ENT_QUOTES, 'UTF-8')); ?></em></p>
<?php endif; ?>

<?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
<p><a href="<?php echo url('temas/novoDesafio/' . (int) $tema['id']); ?>">+ Novo desafio</a></p>
<?php endif; ?>

<?php if (empty($desafios)): ?>
    <p>Nenhum desafio cadastrado neste tema.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Pergunta</th><th>Ativo</th><th>Ações</th></tr>
        <?php foreach ($desafios as $desafio): ?>
        <tr>
            <td><?php echo htmlspecialchars($desafio['pergunta'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $desafio['ativo'] ? 'Sim' : 'Não'; ?></td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('temas/editarDesafio/' . (int) $desafio['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                    <form method="post" action="<?php echo url('temas/removerDesafio'); ?>" onsubmit="return confirm('Remover este desafio? Só funciona se ele ainda não tiver equipes vinculadas.');">
                        <input type="hidden" name="id" value="<?php echo (int) $desafio['id']; ?>">
                        <input type="hidden" name="tema_id" value="<?php echo (int) $tema['id']; ?>">
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
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
