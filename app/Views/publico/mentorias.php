<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <a href="<?php echo url('home/index'); ?>" class="btn">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
        <h1>Mentorias — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>Lista de horários de mentoria, só pra transparência — o agendamento é feito pelo painel de cada equipe.</p>

        <?php if (empty($horarios)): ?>
            <p><em>Nenhum horário de mentoria cadastrado ainda.</em></p>
        <?php else: ?>
            <table border="1" cellpadding="6">
                <tr><th>Mentor</th><th>Início</th><th>Fim</th><th>Observação</th><th>Status</th></tr>
                <?php foreach ($horarios as $horario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($horario['mentor_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($horario['data_inicio'])); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($horario['data_fim'])); ?></td>
                    <td><?php echo htmlspecialchars((string) $horario['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php if ($horario['equipe_id'] !== null): ?>
                            <span class="status-pill laranja">Reservado</span>
                        <?php else: ?>
                            <span class="status-pill verde">Vago</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
