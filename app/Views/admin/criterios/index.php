<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Critérios de <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $etapa['trilha_id']); ?>">Voltar às etapas</a></p>
<p><a href="<?php echo url('criterios/novo/' . (int) $etapa['id']); ?>">+ Novo critério</a></p>

<p>Soma dos pesos: <strong><?php echo number_format($somaPesos, 2, ',', '.'); ?></strong>
    (a fórmula <em>média ponderada de critérios</em> usa esta soma como denominador — os editais 2026 usam pesos que somam 10)</p>

<?php if (empty($criterios)): ?>
    <p>Nenhum critério cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Código</th><th>Nome</th><th>Peso</th><th>Escala</th><th>Ações</th></tr>
        <?php foreach ($criterios as $criterio): ?>
        <tr>
            <td><?php echo (int) $criterio['ordem']; ?></td>
            <td><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $criterio['peso'], 2, ',', '.'); ?></td>
            <td><?php echo number_format((float) $criterio['escala_min'], 1, ',', '.'); ?> a <?php echo number_format((float) $criterio['escala_max'], 1, ',', '.'); ?></td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('criterios/editar/' . (int) $criterio['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form method="post" action="<?php echo url('criterios/mover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $criterio['id']; ?>">
                        <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                        <input type="hidden" name="direcao" value="cima">
                        <button type="submit" class="btn-icone" title="Mover para cima">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="19" x2="12" y2="5"></line>
                                <polyline points="5 12 12 5 19 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <form method="post" action="<?php echo url('criterios/mover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $criterio['id']; ?>">
                        <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                        <input type="hidden" name="direcao" value="baixo">
                        <button type="submit" class="btn-icone" title="Mover para baixo">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <polyline points="19 12 12 19 5 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <form method="post" action="<?php echo url('criterios/remover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $criterio['id']; ?>">
                        <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
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
