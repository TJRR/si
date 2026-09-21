<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Inscritos: <?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('atividades/exportarEjurr/' . (int) $atividade['id']); ?>" class="btn-acao">Exportar (.csv)</a>
        <a href="<?php echo url('atividades/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p>
    <strong>Ocupação:</strong>
    <?php echo count($confirmadas); ?> confirmada<?php echo count($confirmadas) === 1 ? '' : 's'; ?>
    <?php echo $atividade['vagas'] !== null ? ' / ' . (int) $atividade['vagas'] . ' vagas' : ' (vagas ilimitadas)'; ?>
    <?php if (!empty($espera)): ?>
        ; <?php echo count($espera); ?> na lista de espera
    <?php endif; ?>
</p>

<h2>Confirmadas</h2>
<?php if (empty($confirmadas)): ?>
    <p>Ninguém confirmado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>E-mail</th><th>Inscrito em</th></tr>
        <?php foreach ($confirmadas as $inscricao): ?>
        <tr>
            <td><?php echo htmlspecialchars($inscricao['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($inscricao['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($inscricao['inscrito_em']), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Lista de espera</h2>
<?php if (empty($espera)): ?>
    <p>Ninguém na lista de espera.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>E-mail</th><th>Inscrito em</th><th>Ações</th></tr>
        <?php foreach ($espera as $inscricao): ?>
        <tr>
            <td><?php echo htmlspecialchars($inscricao['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($inscricao['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($inscricao['inscrito_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('atividades/confirmarEspera'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $inscricao['id']; ?>">
                    <button type="submit" class="btn-icone" title="Confirmar vaga">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
