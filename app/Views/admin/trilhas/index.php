<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Trilhas de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('concursos/index'); ?>">Voltar aos concursos</a></p>
<?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
<p><a href="<?php echo url('trilhas/novo/' . (int) $concurso['id']); ?>">+ Nova trilha</a>
| <a href="<?php echo url('formularios/index/' . (int) $concurso['id']); ?>">Formulários deste concurso</a></p>
<?php endif; ?>

<?php if (empty($trilhas)): ?>
    <p>Nenhuma trilha cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Ordem</th><th>Ativo</th><th>Inscrições</th><th>Ações</th></tr>
        <?php foreach ($trilhas as $trilha): ?>
        <tr>
            <td><?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $trilha['ordem']; ?></td>
            <td><?php echo $trilha['ativo'] ? 'Sim' : 'Não'; ?></td>
            <td>
                <?php if ($trilha['etapa_cadastro_id'] === null): ?>
                    <em>sem etapa de cadastro</em>
                <?php else: ?>
                    <strong><?php echo $trilha['inscricoes_abertas'] ? 'Abertas' : 'Fechadas'; ?></strong>
                    <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                    <form method="post" action="<?php echo url('trilhas/alternarInscricoes/' . (int) $trilha['id']); ?>" style="display:inline;">
                        <?php if ($trilha['inscricoes_abertas']): ?>
                        <button type="submit" class="btn-icone" title="Fechar inscrições">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </button>
                        <?php else: ?>
                        <button type="submit" class="btn-icone" title="Abrir inscrições">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('trilhas/editar/' . (int) $trilha['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                    <form method="post" action="<?php echo url('trilhas/remover'); ?>" onsubmit="return confirm('Remover esta trilha? Só funciona se ela ainda não tiver etapas, equipes ou outros dados vinculados.');">
                        <input type="hidden" name="id" value="<?php echo (int) $trilha['id']; ?>">
                        <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
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
