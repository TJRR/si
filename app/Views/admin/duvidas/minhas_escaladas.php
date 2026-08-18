<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Dúvidas</h1>
<p>Dúvidas escaladas para você — responda ou, se não for a pessoa certa, escale para outro colaborador.</p>
<p><a href="<?php echo url('requerimentoAdmin/minhasEscaladas'); ?>">Ver meus requerimentos escalados</a></p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="get" action="<?php echo config('base_path'); ?>/index.php">
    <input type="hidden" name="r" value="duvidaAdmin/minhasEscaladas">
    <label>
        <select name="status" onchange="this.form.submit()" aria-label="Estado">
            <option value="" <?php echo empty($statusFiltro) ? 'selected' : ''; ?>>Pendentes</option>
            <option value="respondida" <?php echo $statusFiltro === 'respondida' ? 'selected' : ''; ?>>Respondidas</option>
            <option value="todas" <?php echo $statusFiltro === 'todas' ? 'selected' : ''; ?>>Todas</option>
        </select>
    </label>
</form>

<?php if (empty($minhasEscaladas)): ?>
    <p>Nenhuma dúvida neste filtro.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Pergunta</th><th>Equipe</th><th>Trilha</th><th>Registrada em</th><th>Status</th><th>SLA</th><th>Ações</th></tr>
        <?php foreach ($minhasEscaladas as $duvida): ?>
        <?php $urlVer = url('duvidaAdmin/ver/' . (int) $duvida['id']); ?>
        <tr>
            <td><?php echo htmlspecialchars(mb_substr($duvida['pergunta'], 0, 60) . (mb_strlen($duvida['pergunta']) > 60 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($duvida['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($duvida['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($duvida['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="status-pill <?php echo $duvida['status'] === 'respondida' ? 'verde' : 'laranja'; ?>"><?php echo $duvida['status'] === 'respondida' ? 'Respondida' : 'Em análise'; ?></span></td>
            <td>
                <?php if (!empty($duvida['atrasada'])): ?>
                    <span class="status-pill vermelho">Atrasada</span>
                <?php else: ?>
                    <span class="status-pill verde">Em dia</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Ver">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </a>
                    <?php if ($duvida['status'] !== 'respondida'): ?>
                        <a href="<?php echo htmlspecialchars($urlVer . '#responder', ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Responder">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="9 17 4 12 9 7"></polyline>
                                <path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>
                            </svg>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlVer . '#escalar', ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Escalar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 17 20 12 15 7"></polyline>
                                <path d="M4 18v-2a4 4 0 0 1 4-4h12"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
