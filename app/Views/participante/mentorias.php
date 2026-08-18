<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Mentorias</h1>

<p><a href="<?php echo url('participante/index'); ?>">Voltar ao painel</a></p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<h2>Suas reservas</h2>
<?php if (empty($reservas)): ?>
    <p>Sua equipe ainda não reservou nenhum horário de mentoria.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Mentor</th><th>Início</th><th>Fim <?php echo sufixoFusoHorario(); ?></th><th>Meet</th><th>Observação</th><th>Ações</th></tr>
        <?php foreach ($reservas as $reserva): ?>
        <tr>
            <td><?php echo htmlspecialchars($reserva['mentor_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($reserva['data_inicio']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($reserva['data_fim']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if (linkHttpValido($reserva['link_meet'])): ?>
                    <a href="<?php echo htmlspecialchars($reserva['link_meet'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Entrar</a>
                <?php elseif (!empty($reserva['integracao_google']) && !empty($reserva['meet_pendente'])): ?>
                    <span class="status-pill laranja">Gerando sala...</span>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars((string) $reserva['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('mentoria/cancelar/' . (int) $reserva['id']); ?>" onsubmit="return confirm('Cancelar esta reserva?');"><?= campoCsrf() ?>
                    <button type="submit">Cancelar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Horários disponíveis</h2>
<?php if (empty($vagos)): ?>
    <p>Nenhum horário disponível no momento.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Mentor</th><th>Início</th><th>Fim <?php echo sufixoFusoHorario(); ?></th><th>Observação</th><th>Ações</th></tr>
        <?php foreach ($vagos as $vaga): ?>
        <tr>
            <td><?php echo htmlspecialchars($vaga['mentor_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($vaga['data_inicio']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($vaga['data_fim']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $vaga['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('mentoria/reservar/' . (int) $vaga['id']); ?>"><?= campoCsrf() ?>
                    <button type="submit">Reservar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
