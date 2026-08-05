<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Oficinas</h1>

<p><a href="<?php echo url('participante/index'); ?>">Voltar ao painel</a></p>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p>Encontros coletivos com tema pré-definido — sua equipe pode se inscrever em quantas oficinas quiser, sem exclusividade.</p>

<?php if (empty($horarios)): ?>
    <p>Nenhuma oficina disponível no momento.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Tema</th><th>Início</th><th>Fim</th><th>Meet</th><th>Observação</th><th>Ações</th></tr>
        <?php foreach ($horarios as $horario): ?>
        <?php $inscrita = in_array((int) $horario['id'], $inscritosIds, true); ?>
        <tr>
            <td><?php echo htmlspecialchars($horario['tema'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($horario['data_inicio'])); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($horario['data_fim'])); ?></td>
            <td>
                <?php if ($inscrita && linkHttpValido($horario['link_meet'])): ?>
                    <a href="<?php echo htmlspecialchars($horario['link_meet'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Entrar</a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars((string) $horario['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if ($inscrita): ?>
                    <span class="status-pill verde">Inscrita</span>
                    <form method="post" action="<?php echo url('oficina/cancelar/' . (int) $horario['id']); ?>" onsubmit="return confirm('Cancelar a inscrição nesta oficina?');">
                        <button type="submit">Cancelar</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?php echo url('oficina/inscrever/' . (int) $horario['id']); ?>">
                        <button type="submit">Inscrever-se</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
