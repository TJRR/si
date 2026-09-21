<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Presenças: <?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('atividades/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (empty($checkins)): ?>
    <p>Ninguém confirmou presença ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>E-mail</th><th>Horário</th><th>Presença efetiva</th></tr>
        <?php foreach ($checkins as $checkin): ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($checkin['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (empty($checkin['inscricao_ativa'])): ?>
                    <span class="status-pill laranja">Inscrição cancelada</span>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($checkin['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($checkin['checkin_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if ($checkin['presenca_efetiva']): ?>
                    <span class="status-pill verde">Sim</span>
                <?php else: ?>
                    <span class="status-pill vermelho">Não</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
