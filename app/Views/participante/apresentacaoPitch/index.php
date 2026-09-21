<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Apresentação de pitch da etapa <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('participante/index'); ?>">Voltar ao painel</a></p>

<?php if (!empty($flash)): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($reserva !== null): ?>
    <h2>Seu horário</h2>
    <p>
        <strong><?php echo htmlspecialchars(formatarDataHora($reserva['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
        às <?php echo htmlspecialchars(formatarDataHora($reserva['data_fim']), ENT_QUOTES, 'UTF-8'); ?> <?php echo sufixoFusoHorario(); ?></strong>
        na modalidade <?php echo $reserva['modalidade'] === 'online' ? 'online' : 'presencial'; ?>.
    </p>
    <?php if ($reserva['modalidade'] === 'presencial'): ?>
        <p>Endereço: <?php echo htmlspecialchars((string) ($config['endereco_presencial'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php elseif (linkHttpValido($reserva['link_meet'])): ?>
        <p><a href="<?php echo htmlspecialchars($reserva['link_meet'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn-acao">Entrar na sala do Google Meet</a></p>
        <p><small>O convite também chega pela sua Google Agenda institucional.</small></p>
    <?php elseif (!empty($reserva['integracao_google']) && !empty($reserva['meet_pendente'])): ?>
        <p>Gerando a sala do Google Meet. Atualize esta página em alguns instantes.</p>
    <?php else: ?>
        <p>O sistema ainda não conseguiu conectar com o Google Agenda. O hiperlink da sala será enviado assim que possível.</p>
    <?php endif; ?>
<?php elseif (!$dentroDaJanela): ?>
    <?php if ($config !== null && !empty($config['janela_escolha_inicio'])): ?>
        <p>A escolha de data e horário ainda não está aberta. Abre em <?php echo htmlspecialchars(formatarDataHora($config['janela_escolha_inicio']), ENT_QUOTES, 'UTF-8'); ?> <?php echo sufixoFusoHorario(); ?>.</p>
    <?php else: ?>
        <p>A escolha de data e horário ainda não está aberta.</p>
    <?php endif; ?>
<?php elseif (empty($vagos)): ?>
    <p>Não há horários disponíveis no momento.</p>
<?php else: ?>
    <h2>Escolha um horário</h2>
    <table border="1" cellpadding="6">
        <tr><th>Início</th><th>Fim <?php echo sufixoFusoHorario(); ?></th><th>Modalidade</th><th>Ações</th></tr>
        <?php foreach ($vagos as $vaga): ?>
        <tr>
            <td><?php echo htmlspecialchars(formatarDataHora($vaga['data_inicio']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($vaga['data_fim']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <select name="modalidade" form="reservar-<?php echo (int) $vaga['id']; ?>" required>
                    <option value="">Selecione a modalidade</option>
                    <option value="online">Online (Google Meet)</option>
                    <option value="presencial">Presencial</option>
                </select>
            </td>
            <td>
                <form id="reservar-<?php echo (int) $vaga['id']; ?>" method="post" action="<?php echo url('apresentacaoPitch/reservar/' . (int) $etapa['id'] . '/' . (int) $vaga['id']); ?>"><?= campoCsrf() ?>
                    <button type="submit" class="btn-icone" title="Reservar este horário">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                            <path d="M9 16l2 2 4-4"></path>
                        </svg>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
