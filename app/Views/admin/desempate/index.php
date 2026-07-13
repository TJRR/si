<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Regras de desempate — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>
<p><a href="<?php echo url('desempate/novo/' . (int) $trilha['id']); ?>">+ Novo critério de desempate</a></p>

<p>Ordem de aplicação em caso de empate na Nota Final (1ª linha tem prioridade):</p>

<?php if (empty($regras)): ?>
    <p>Nenhuma regra de desempate cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Etapa</th><th>Critério</th><th>Direção</th><th>Ações</th></tr>
        <?php foreach ($regras as $regra): ?>
        <tr>
            <td><?php echo (int) $regra['ordem']; ?></td>
            <td><?php echo htmlspecialchars($regra['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($regra['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $regra['direcao'] === 'asc' ? 'Crescente' : 'Decrescente (maior nota vence)'; ?></td>
            <td>
                <div class="acoes-icones">
                    <form method="post" action="<?php echo url('desempate/mover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                        <input type="hidden" name="direcao" value="cima">
                        <button type="submit" class="btn-icone" title="Mover para cima">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="19" x2="12" y2="5"></line>
                                <polyline points="5 12 12 5 19 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <form method="post" action="<?php echo url('desempate/mover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                        <input type="hidden" name="direcao" value="baixo">
                        <button type="submit" class="btn-icone" title="Mover para baixo">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <polyline points="19 12 12 19 5 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <form method="post" action="<?php echo url('desempate/remover'); ?>">
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
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
