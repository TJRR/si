<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Concursos</h1>
    <div class="pagina-titulo-botoes">
        <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
        <a href="<?php echo url('concursos/novo'); ?>" class="btn-acao">+ Novo concurso</a>
        <?php endif; ?>
        <a href="<?php echo url('home/administrativo'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (empty($concursos)): ?>
    <p>Nenhum concurso cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Status</th><th>Período</th><th>Ações</th></tr>
        <?php foreach ($concursos as $concurso): ?>
        <tr>
            <td><?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($concurso['status'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if ($concurso['data_inicio'] === null && $concurso['data_fim'] === null): ?>
                    Período não definido
                <?php else: ?>
                    <?php echo htmlspecialchars(formatarData($concurso['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                    a
                    <?php echo htmlspecialchars(formatarData($concurso['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?php echo url('trilhas/index/' . (int) $concurso['id']); ?>">Trilhas</a>
                <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                &nbsp;|&nbsp;
                <a href="<?php echo url('tema/index/' . (int) $concurso['id']); ?>">Identidade visual</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                <div class="acoes-icones">
                    <a href="<?php echo url('concursos/editar/' . (int) $concurso['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form method="post" action="<?php echo url('concursos/remover'); ?>" onsubmit="return confirm('Remover este concurso? Só funciona se ele ainda não tiver trilhas, formulários ou categorias de avaliador vinculados.');">
                        <input type="hidden" name="id" value="<?php echo (int) $concurso['id']; ?>">
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
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
