<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Mentorias de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('mentoriaAdmin/novo/' . (int) $concurso['id']); ?>" class="btn-acao">+ Novo horário</a>
    </div>
</div>
<p>Qualquer administrador ou suporte pode criar horários de mentoria pra si mesmo — a equipe reserva o horário pelo próprio painel. Você só pode editar/remover os horários que você mesmo criou (Administrador pode remover qualquer um, para moderação).</p>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (empty($horarios)): ?>
    <p>Nenhum horário cadastrado ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Mentor</th><th>Início</th><th>Fim</th><th>Observação</th><th>Status</th><th>Ações</th></tr>
            <?php foreach ($horarios as $horario): ?>
            <tr>
                <td><?php echo htmlspecialchars($horario['mentor_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($horario['data_inicio'])); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($horario['data_fim'])); ?></td>
                <td><?php echo htmlspecialchars((string) $horario['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if ($horario['equipe_id'] !== null): ?>
                        <span class="status-pill laranja">Reservado — <?php echo htmlspecialchars($horario['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php else: ?>
                        <span class="status-pill verde">Vago</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ((int) $horario['mentor_usuario_id'] === (int) \App\Core\Auth::usuarioId() || \App\Core\Auth::possuiPerfil('administrador')): ?>
                        <form method="post" action="<?php echo url('mentoriaAdmin/remover'); ?>" onsubmit="return confirm('Remover este horário?<?php echo $horario['equipe_id'] !== null ? ' A equipe que reservou sera notificada.' : ''; ?>');">
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
