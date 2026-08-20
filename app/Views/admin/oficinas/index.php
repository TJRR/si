<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Oficinas de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('oficinaAdmin/novo/' . (int) $concurso['id']); ?>" class="btn-acao">+ Novo horário</a>
    </div>
</div>
<p>Encontro coletivo com tema pré-definido — qualquer equipe interessada pode se inscrever, sem limite de vagas. Você só pode remover os horários que você mesmo criou (Administrador pode remover qualquer um, para moderação).</p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<?php if (empty($horarios)): ?>
    <p>Nenhum horário cadastrado ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Tema</th><th>Início</th><th>Fim <?php echo sufixoFusoHorario(); ?></th><th>Restrito a</th><th>Meet</th><th>Google Agenda</th><th>Observação</th><th>Inscritas</th><th>Criado por</th><th>Ações</th></tr>
            <?php foreach ($horarios as $horario): ?>
            <tr>
                <td><?php echo htmlspecialchars($horario['tema'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(formatarDataHora($horario['data_inicio']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(formatarDataHora($horario['data_fim']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if (empty($horario['etapa_nome'])): ?>
                        <span class="status-pill">Aberto a todos</span>
                    <?php else: ?>
                        <span class="status-pill laranja" title="Só enxerga e se inscreve quem está habilitado a esta etapa"><?php echo htmlspecialchars($horario['etapa_trilha_nome'] . ' — ' . $horario['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (linkHttpValido($horario['link_meet'])): ?>
                        <a href="<?php echo htmlspecialchars($horario['link_meet'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                    <?php elseif (!empty($horario['integracao_google']) && !empty($horario['meet_pendente'])): ?>
                        <span class="status-pill laranja">Gerando sala...</span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (empty($horario['integracao_google'])): ?>
                        —
                    <?php else: ?>
                        <?php if (empty($horario['google_event_id'])): ?>
                            <span class="status-pill vermelho">Falha na integração</span>
                        <?php elseif (!empty($horario['meet_pendente'])): ?>
                            <span class="status-pill laranja">Gerando sala...</span>
                        <?php else: ?>
                            <span class="status-pill verde">Integrado</span>
                        <?php endif; ?>
                        <?php if (!empty($horario['google_sincronizado_em'])): ?>
                            <br><small>Verificado em <?php echo htmlspecialchars(formatarDataHora($horario['google_sincronizado_em']), ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($horario['convite_status'])): ?>
                            <br>
                            <?php foreach ($horario['convite_status'] as $convite): ?>
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
                        <form method="post" action="<?php echo url('oficinaAdmin/verificarNovamente'); ?>"><?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?php echo (int) $horario['id']; ?>">
                            <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
                            <button type="submit">Verificar/Tentar novamente</button>
                        </form>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars((string) $horario['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if ((int) $horario['total_inscritos'] > 0): ?>
                        <a href="<?php echo url('oficinaAdmin/inscritos/' . (int) $horario['id']); ?>"
                           onclick="abrirModalUrl('Equipes inscritas', this.href); return false;" title="Ver equipes inscritas">
                            <span class="status-pill verde"><?php echo (int) $horario['total_inscritos']; ?></span>
                        </a>
                    <?php else: ?>
                        <span class="status-pill"><?php echo (int) $horario['total_inscritos']; ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($horario['criado_por_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php // Fase 32: presenca real so' existe pra horario integrado ao Google e ja encerrado. ?>
                    <?php if (!empty($horario['integracao_google']) && strtotime($horario['data_fim']) < time()): ?>
                        <a href="<?php echo url('oficinaAdmin/presenca/' . (int) $horario['id']); ?>"
                           onclick="abrirModalUrl('Presença na sala do Meet', this.href); return false;"
                           class="btn-icone" title="Ver presença na sala do Meet">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <polyline points="16 11 18 13 22 9"></polyline>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <?php // Fase 34: horario ja iniciado nao pode mais ser editado nem removido (o servidor tambem barra). ?>
                    <?php $podeAlterar = strtotime($horario['data_inicio']) > time(); ?>
                    <?php if ($podeAlterar && ((int) $horario['criado_por'] === (int) \App\Core\Auth::usuarioId() || \App\Core\Auth::possuiPerfil('administrador'))): ?>
                        <a href="<?php echo url('oficinaAdmin/editar/' . (int) $horario['id']); ?>" class="btn-icone" title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($podeAlterar && ((int) $horario['criado_por'] === (int) \App\Core\Auth::usuarioId() || \App\Core\Auth::possuiPerfil('administrador'))): ?>
                        <form method="post" action="<?php echo url('oficinaAdmin/remover'); ?>" onsubmit="return confirm('Remover este horário?<?php echo (int) $horario['total_inscritos'] > 0 ? ' As equipes inscritas serão notificadas.' : ''; ?>');"><?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?php echo (int) $horario['id']; ?>">
                            <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
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

<h2>Equipes sem participação em eventos</h2>
<p>Equipes inscritas e aprovadas que ainda não se inscreveram em nenhuma oficina nem reservaram nenhuma mentoria. Sem uma etapa selecionada, "aprovada" considera a homologação do cadastro; escolhendo uma etapa além da primeira, passa a considerar a classificação obtida na etapa anterior.</p>

<form method="get" action="<?php echo config('base_path'); ?>/index.php">
    <input type="hidden" name="r" value="oficinaAdmin/index/<?php echo (int) $concurso['id']; ?>">
    <label>Trilha:
        <select name="trilha_id" onchange="this.form.submit()">
            <option value="" <?php echo $trilhaFiltro === null ? 'selected' : ''; ?>>Todas</option>
            <?php foreach ($trilhas as $trilha): ?>
                <option value="<?php echo (int) $trilha['id']; ?>" <?php echo $trilhaFiltro === (int) $trilha['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if ($trilhaFiltro !== null): ?>
        <label>Etapa:
            <select name="etapa_id" onchange="this.form.submit()">
                <option value="" <?php echo $etapaFiltro === null ? 'selected' : ''; ?>>— homologação de cadastro —</option>
                <?php foreach ($etapasDaTrilha as $etapa): ?>
                    <option value="<?php echo (int) $etapa['id']; ?>" <?php echo $etapaFiltro === (int) $etapa['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>
</form>

<?php if (empty($equipesSemParticipacao) && !empty($etapaAindaNaoIniciada)): ?>
    <p>Esta etapa terá início em <?php echo htmlspecialchars(formatarDataHora($etapaSelecionada['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> <?php echo sufixoFusoHorario(); ?> — ainda não há equipes aprovadas para ela.</p>
<?php elseif (empty($equipesSemParticipacao)): ?>
    <p>Nenhuma equipe pendente — todas já participaram de algum evento.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Equipe</th><th>Trilha</th></tr>
            <?php foreach ($equipesSemParticipacao as $equipe): ?>
            <tr>
                <td><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($equipe['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
