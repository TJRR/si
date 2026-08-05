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
        <h1>Oficinas — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>Lista de encontros coletivos, só pra transparência — a inscrição é feita pelo painel de cada equipe.</p>

        <?php if (empty($horarios)): ?>
            <p><em>Nenhuma oficina cadastrada ainda.</em></p>
        <?php else: ?>
            <table border="1" cellpadding="6">
                <tr><th>Tema</th><th>Início</th><th>Fim</th><th>Observação</th><th>Equipes inscritas</th></tr>
                <?php foreach ($horarios as $horario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($horario['tema'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($horario['data_inicio'])); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($horario['data_fim'])); ?></td>
                    <td><?php echo htmlspecialchars((string) $horario['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php if (empty($horario['inscritos'])): ?>
                            <span class="status-pill laranja">Nenhuma ainda</span>
                        <?php else: ?>
                            <?php echo htmlspecialchars(implode(', ', array_column($horario['inscritos'], 'nome_equipe')), ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
