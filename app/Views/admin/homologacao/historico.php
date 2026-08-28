<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
    $rotulosStatus = ['pendente' => 'Pendente', 'homologado' => 'Homologado', 'rejeitado' => 'Rejeitado'];
    $origensRotulo = [
        'homologacao' => 'Homologação',
        'rejeicao' => 'Rejeição',
        'correcao_cpf' => 'Correção de CPF',
        'retroativo' => 'Registro retroativo',
    ];
?>
<?php if (empty($transicoes)): ?>
    <p>Nenhuma transição registrada para este vínculo.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Data <?php echo sufixoFusoHorario(); ?></th>
            <th>De</th>
            <th>Para</th>
            <th>Quem</th>
            <th>Motivo</th>
        </tr>
        <?php foreach ($transicoes as $transicao): ?>
        <tr>
            <td><?php echo htmlspecialchars(formatarDataHora($transicao['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($rotulosStatus[$transicao['status_anterior']], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($rotulosStatus[$transicao['status_novo']], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($transicao['usuario_nome'] !== null ? $transicao['usuario_nome'] : $origensRotulo[$transicao['origem']], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $transicao['motivo'] !== null ? htmlspecialchars($transicao['motivo'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
