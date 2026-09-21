<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Apresentação de pitch: <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p>Agendamento das apresentações orais desta etapa.</p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<h2>Configuração</h2>
<form method="post" action="<?php echo url('apresentacaoPitchAdmin/salvarConfig'); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">

    <label>Início da janela para as equipes escolherem data/horário:
        <input type="datetime-local" name="janela_escolha_inicio" value="<?php echo $config !== null && !empty($config['janela_escolha_inicio']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($config['janela_escolha_inicio'])), ENT_QUOTES, 'UTF-8') : ''; ?>">
    </label><br>
    <label>Fim da janela:
        <input type="datetime-local" name="janela_escolha_fim" value="<?php echo $config !== null && !empty($config['janela_escolha_fim']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($config['janela_escolha_fim'])), ENT_QUOTES, 'UTF-8') : ''; ?>">
    </label><br>

    <label>E-mail organizador do Google Agenda (conta institucional @tjrr.jus.br, onde os eventos das apresentações online são criados):
        <input type="email" name="email_organizador_google" maxlength="190" placeholder="npi@tjrr.jus.br" value="<?php echo $config !== null ? htmlspecialchars((string) $config['email_organizador_google'], ENT_QUOTES, 'UTF-8') : ''; ?>">
    </label><br>

    <label>Endereço da apresentação presencial (mostrado à equipe que escolher essa modalidade):
        <input type="text" name="endereco_presencial" maxlength="255" placeholder="Sede Administrativa Luiz Rosalvo Indrusiak Fin, Sala 415" value="<?php echo $config !== null ? htmlspecialchars((string) $config['endereco_presencial'], ENT_QUOTES, 'UTF-8') : ''; ?>">
    </label>

    <div class="form-acoes">
        <button type="submit">Salvar configuração</button>
    </div>
</form>

<h2>Horários</h2>
<form method="post" action="<?php echo url('apresentacaoPitchAdmin/novoSlot/' . (int) $etapa['id']); ?>" style="margin-bottom:1em;"><?= campoCsrf() ?>
    <label>Início:
        <input type="datetime-local" name="data_inicio" required>
    </label>
    <label>Fim:
        <input type="datetime-local" name="data_fim" required>
    </label>
    <button type="submit">+ Novo horário</button>
</form>

<?php if (empty($slots)): ?>
    <p>Nenhum horário cadastrado ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Início</th><th>Fim <?php echo sufixoFusoHorario(); ?></th><th>Equipe</th><th>Modalidade</th><th>Meet</th><th>Google Agenda</th><th>Ações</th></tr>
            <?php foreach ($slots as $slot): ?>
            <tr>
                <td><?php echo htmlspecialchars(formatarDataHora($slot['data_inicio']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(formatarDataHora($slot['data_fim']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if ($slot['equipe_id'] !== null): ?>
                        <span class="status-pill laranja"><?php echo htmlspecialchars($slot['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php else: ?>
                        <span class="status-pill verde">Vago</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $slot['modalidade'] !== null ? ($slot['modalidade'] === 'online' ? 'Online' : 'Presencial') : 'Não informado'; ?></td>
                <td>
                    <?php if (linkHttpValido($slot['link_meet'])): ?>
                        <a href="<?php echo htmlspecialchars($slot['link_meet'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                    <?php elseif (!empty($slot['integracao_google']) && !empty($slot['meet_pendente'])): ?>
                        <span class="status-pill laranja">Gerando sala...</span>
                    <?php else: ?>
                        Não informado
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (empty($slot['integracao_google'])): ?>
                        Não informado
                    <?php else: ?>
                        <?php if (empty($slot['google_event_id'])): ?>
                            <span class="status-pill vermelho">Falha na integração</span>
                        <?php elseif (!empty($slot['meet_pendente'])): ?>
                            <span class="status-pill laranja">Gerando sala...</span>
                        <?php else: ?>
                            <span class="status-pill verde">Integrado</span>
                        <?php endif; ?>
                        <?php if (!empty($slot['convite_status'])): ?>
                            <br>
                            <?php foreach ($slot['convite_status'] as $convite): ?>
                                <?php
                                $mapaStatus = [
                                    'accepted' => ['Confirmado', 'verde'],
                                    'declined' => ['Recusado', 'vermelho'],
                                    'tentative' => ['Talvez', 'laranja'],
                                    'needsAction' => ['Aguardando resposta', 'laranja'],
                                ];
                                $rotulo = isset($mapaStatus[$convite['status']]) ? $mapaStatus[$convite['status']] : ['Aguardando resposta', 'laranja'];
                                ?>
                                <span class="status-pill <?php echo $rotulo[1]; ?>" title="<?php echo htmlspecialchars($convite['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($convite['participante_nome'] ?: $convite['email'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $rotulo[0]; ?></span><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <form method="post" action="<?php echo url('apresentacaoPitchAdmin/verificarNovamente'); ?>"><?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?php echo (int) $slot['id']; ?>">
                            <button type="submit">Verificar/Tentar novamente</button>
                        </form>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($slot['integracao_google']) && strtotime($slot['data_fim']) < time()): ?>
                        <a href="<?php echo url('apresentacaoPitchAdmin/presenca/' . (int) $slot['id']); ?>"
                           onclick="abrirModalUrl('Presença na sala do Meet', this.href); return false;"
                           class="btn-icone" title="Ver presença na sala do Meet">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <polyline points="16 11 18 13 22 9"></polyline>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($slot['equipe_id'] === null): ?>
                        <a href="<?php echo url('apresentacaoPitchAdmin/editarSlot/' . (int) $slot['id']); ?>" class="btn-icone" title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <form method="post" action="<?php echo url('apresentacaoPitchAdmin/removerSlot'); ?>" onsubmit="return confirm('Remover este horário?');"><?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?php echo (int) $slot['id']; ?>">
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
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<h2>Atribuir manualmente</h2>
<p>Para equipes classificadas que não se manifestaram dentro da janela, a atribuição é sempre no formato online, em um dos horários ainda vagos.</p>
<?php
$slotsVagos = array_values(array_filter($slots, function ($slot) {
    return $slot['equipe_id'] === null;
}));
?>
<?php if (empty($equipesParaAtribuir)): ?>
    <p>Nenhuma equipe classificada pendente de atribuição.</p>
<?php elseif (empty($slotsVagos)): ?>
    <p>Não há horários vagos para atribuir.</p>
<?php else: ?>
    <form method="post" action="<?php echo url('apresentacaoPitchAdmin/atribuir'); ?>"><?= campoCsrf() ?>
        <label>Equipe:
            <select name="equipe_id" required>
                <?php foreach ($equipesParaAtribuir as $equipe): ?>
                    <option value="<?php echo (int) $equipe['equipe_id']; ?>"><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Horário:
            <select name="id" required>
                <?php foreach ($slotsVagos as $slot): ?>
                    <option value="<?php echo (int) $slot['id']; ?>"><?php echo htmlspecialchars(formatarDataHora($slot['data_inicio']) . ' até ' . formatarDataHora($slot['data_fim']), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Atribuir</button>
    </form>
<?php endif; ?>
