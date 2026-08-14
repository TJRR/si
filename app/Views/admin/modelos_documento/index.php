<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Modelos de Documento — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('modelosDocumento/novo/' . (int) $etapa['id']); ?>" class="btn-acao">+ Novo modelo</a></p>

<?php if (empty($modelos)): ?>
    <p>Nenhum modelo cadastrado ainda.</p>
<?php else: ?>
    <ul class="reordenar-lista" data-reordenar-rota="modelosDocumento/reordenar/<?php echo (int) $etapa['id']; ?>">
        <?php foreach ($modelos as $indice => $modelo): ?>
        <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $modelo['id']; ?>">
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($modelo['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span class="status-pill <?php echo $modelo['ativo'] ? 'verde' : 'cinza'; ?>">
                    <?php echo $modelo['ativo'] ? 'Ativo' : 'Inativo'; ?>
                </span>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('modelosDocumento/editar/' . (int) $modelo['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <form method="post" action="<?php echo url('modelosDocumento/remover'); ?>" onsubmit="return confirm('Remover este modelo?');">
                    <input type="hidden" name="id" value="<?php echo (int) $modelo['id']; ?>">
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
            <div class="reordenar-botoes">
                <button type="button" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($modelos) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2 style="margin-top:1.5rem;">Retenção de dados</h2>
<div class="admin-card">
    <?php if ($naoTerminaisPendentes > 0): ?>
        <p><?php echo (int) $naoTerminaisPendentes; ?> pedido(s) desta etapa ainda em análise.</p>
    <?php endif; ?>
    <?php if ($etapa['data_fim'] === null || strtotime($etapa['data_fim']) > time()): ?>
        <p>O expurgo de documentos só fica disponível depois que a etapa passar da própria data final.</p>
    <?php else: ?>
        <p>Esta ação apaga fisicamente os PDFs assinados (e os anexos de resposta) dos pedidos já decididos (aprovado/recusado/revogado) desta etapa. Pedidos ainda em análise não são afetados. É irreversível.</p>
        <form method="post" action="<?php echo url('modelosDocumento/expurgar/' . (int) $etapa['id']); ?>" onsubmit="return confirm('Confirma o expurgo definitivo dos documentos já decididos desta etapa?');">
            <label>Digite o nome exato da etapa para confirmar (<strong><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>):
                <input type="text" name="confirmacao" required>
            </label>
            <button type="submit" class="btn-acao">Expurgar documentos desta etapa</button>
        </form>
    <?php endif; ?>
</div>
