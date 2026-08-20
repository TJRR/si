<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Requerimentos — Minhas pendências</h1>

<p><a href="<?php echo url('duvidaAdmin/minhasEscaladas'); ?>">Ver minhas dúvidas escaladas</a></p>

<form method="get" action="<?php echo config('base_path'); ?>/index.php">
    <input type="hidden" name="r" value="requerimentoAdmin/minhasEscaladas">
    <label>Estado:
        <select name="status" onchange="this.form.submit()">
            <option value="" <?php echo empty($statusFiltro) ? 'selected' : ''; ?>>Escaladas (pendentes)</option>
            <option value="aprovado" <?php echo $statusFiltro === 'aprovado' ? 'selected' : ''; ?>>Aprovadas</option>
            <option value="recusado" <?php echo $statusFiltro === 'recusado' ? 'selected' : ''; ?>>Recusadas</option>
            <option value="revogado" <?php echo $statusFiltro === 'revogado' ? 'selected' : ''; ?>>Revogadas</option>
            <option value="todos" <?php echo $statusFiltro === 'todos' ? 'selected' : ''; ?>>Todas</option>
        </select>
    </label>
</form>

<?php if (empty($minhasEscaladas)): ?>
    <p>Nenhum requerimento escalado pra você no momento.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Equipe</th><th>Modelo</th><th>Status</th><th>SLA</th><th>Ações</th></tr>
        <?php
            $rotulosStatus = [
                'recebido' => 'Recebido', 'escalado' => 'Em análise', 'aprovado' => 'Aprovado',
                'recusado' => 'Recusado', 'revogado' => 'Revogado', 'esclarecimento_solicitado' => 'Esclarecimento solicitado',
                'aguardando_assinatura' => 'Aguardando assinatura',
            ];
            $coresStatus = [
                'recebido' => 'azul', 'escalado' => 'laranja', 'aprovado' => 'verde', 'recusado' => 'vermelho',
                'revogado' => 'vermelho', 'esclarecimento_solicitado' => 'roxo', 'aguardando_assinatura' => 'cinza',
            ];
        ?>
        <?php foreach ($minhasEscaladas as $requerimento): ?>
        <tr>
            <td><?php echo htmlspecialchars($requerimento['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($requerimento['modelo_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="status-pill <?php echo $coresStatus[$requerimento['status']]; ?>"><?php echo $rotulosStatus[$requerimento['status']]; ?></span></td>
            <td>
                <?php if (!empty($requerimento['atrasado'])): ?>
                    <span class="status-pill vermelho">Atrasado</span>
                <?php else: ?>
                    <span class="status-pill verde">Em dia</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?php echo url('requerimentoAdmin/ver/' . (int) $requerimento['id']); ?>" class="btn-icone" title="Ver" data-modal-nav
                   onclick="abrirModalNavegavel('Requerimento', this); return false;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
