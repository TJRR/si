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
    <?php if ($podeEditar): ?>
    <p style="color:#555;font-size:0.9em;">
        Remover uma presença é para o caso de ela ter sido registrada por engano. A linha não é apagada:
        ela sai das contagens e das exportações, continua aqui com o motivo, e os pontos dela e os bônus que
        dependiam dela são anulados na hora, com aviso à pessoa. "Restaurar presença" desfaz a remoção com o
        horário original da leitura e devolve esses pontos. Depois do fim da atividade, a pessoa não consegue
        mais confirmar presença pelo aplicativo, então a restauração é o único caminho de volta.
    </p>
    <?php endif; ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Nome</th><th>E-mail</th><th>Horário</th><th>Presença efetiva</th><th>Pontos</th>
            <?php if ($podeEditar): ?><th>Ações</th><?php endif; ?>
        </tr>
        <?php foreach ($checkins as $checkin): ?>
        <?php $removida = $checkin['removido_em'] !== null; ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($checkin['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (empty($checkin['inscricao_ativa'])): ?>
                    <span class="status-pill laranja">Inscrição cancelada</span>
                <?php endif; ?>
                <?php if ($removida): ?>
                    <span class="status-pill vermelho">Removida</span>
                    <br>
                    <small style="color:#555;">
                        Removida por <?php echo htmlspecialchars((string) $checkin['removido_por_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        em <?php echo htmlspecialchars(formatarDataHora($checkin['removido_em']), ENT_QUOTES, 'UTF-8'); ?>.
                        Motivo: <?php echo htmlspecialchars((string) $checkin['motivo_remocao'], ENT_QUOTES, 'UTF-8'); ?>
                    </small>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($checkin['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($checkin['checkin_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if ($removida): ?>
                    <span class="status-pill vermelho">Não conta</span>
                <?php elseif ($checkin['presenca_efetiva']): ?>
                    <span class="status-pill verde">Sim</span>
                <?php else: ?>
                    <span class="status-pill vermelho">Não</span>
                <?php endif; ?>
            </td>
            <td>
                <?php
                $creditoPresenca = isset($creditosPresenca[(int) $checkin['evento_inscricao_id']]) ? $creditosPresenca[(int) $checkin['evento_inscricao_id']] : null;
                ?>
                <?php if ($creditoPresenca === null): ?>
                    <small style="color:#555;">Sem pontos</small>
                <?php else: ?>
                    <?php echo (int) $creditoPresenca['pontos_presenca']; ?>
                    <?php if ((int) $creditoPresenca['pontos_pontualidade'] > 0): ?>
                        + <?php echo (int) $creditoPresenca['pontos_pontualidade']; ?> de pontualidade
                    <?php endif; ?>
                    <?php if ($creditoPresenca['anulado_em'] !== null): ?>
                        <span class="status-pill vermelho">Anulados</span>
                    <?php endif; ?>
                    <br>
                    <small style="color:#555;">
                        <?php $minutosAntes = (int) $creditoPresenca['minutos_antes_do_inicio']; ?>
                        <?php if ($minutosAntes > 0): ?>
                            Leitura <?php echo $minutosAntes; ?> <?php echo $minutosAntes === 1 ? 'minuto' : 'minutos'; ?> antes do início.
                        <?php elseif ($minutosAntes < 0): ?>
                            Leitura <?php echo abs($minutosAntes); ?> <?php echo abs($minutosAntes) === 1 ? 'minuto' : 'minutos'; ?> depois do início.
                        <?php else: ?>
                            Leitura no minuto do início.
                        <?php endif; ?>
                        <?php if ($creditoPresenca['antecedencia_exigida'] !== null): ?>
                            O extra exigia <?php echo (int) $creditoPresenca['antecedencia_exigida']; ?> minutos antes.
                        <?php endif; ?>
                    </small>
                <?php endif; ?>
            </td>
            <?php if ($podeEditar): ?>
            <td>
                <?php if (!$removida): ?>
                <form method="post" action="<?php echo url('atividades/removerCheckin/' . (int) $atividade['id'] . '/' . (int) $checkin['id']); ?>" onsubmit="return confirm('Remover esta presença? Os pontos dela e os bônus que dependiam dela são anulados, e a pessoa é avisada.');"><?= campoCsrf() ?>
                    <label>Motivo:
                        <input type="text" name="motivo" maxlength="500" size="30" required>
                    </label>
                    <button type="submit">Remover</button>
                </form>
                <?php else: ?>
                <form method="post" action="<?php echo url('atividades/restaurarCheckin/' . (int) $atividade['id'] . '/' . (int) $checkin['id']); ?>" onsubmit="return confirm('Restaurar esta presença com o horário original da leitura? Os pontos anulados pela remoção voltam a valer.');"><?= campoCsrf() ?>
                    <button type="submit">Restaurar presença</button>
                </form>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
