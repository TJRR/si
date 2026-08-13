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

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (empty($horarios)): ?>
    <p>Nenhum horário cadastrado ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Tema</th><th>Início</th><th>Fim <?php echo sufixoFusoHorario(); ?></th><th>Meet</th><th>Observação</th><th>Inscritas</th><th>Criado por</th><th>Ações</th></tr>
            <?php foreach ($horarios as $horario): ?>
            <tr>
                <td><?php echo htmlspecialchars($horario['tema'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(formatarDataHora($horario['data_inicio']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(formatarDataHora($horario['data_fim']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if (linkHttpValido($horario['link_meet'])): ?>
                        <a href="<?php echo htmlspecialchars($horario['link_meet'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                    <?php else: ?>
                        —
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
                    <?php if ((int) $horario['criado_por'] === (int) \App\Core\Auth::usuarioId() || \App\Core\Auth::possuiPerfil('administrador')): ?>
                        <form method="post" action="<?php echo url('oficinaAdmin/remover'); ?>" onsubmit="return confirm('Remover este horário?<?php echo (int) $horario['total_inscritos'] > 0 ? ' As equipes inscritas serão notificadas.' : ''; ?>');">
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
