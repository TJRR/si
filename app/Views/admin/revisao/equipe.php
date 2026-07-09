<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('revisao/equipes'); ?>">Voltar a lista de equipes</a></p>

<p>
    Vinculo institucional:
    <?php echo htmlspecialchars((string) $equipe['vinculo_institucional'], ENT_QUOTES, 'UTF-8'); ?>
</p>
<?php if (!empty($equipe['observacoes'])): ?>
    <p>Observacoes: <?php echo nl2br(htmlspecialchars($equipe['observacoes'], ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

<table border="1" cellpadding="6">
    <tr>
        <th>Nome</th>
        <th>Papel</th>
        <th>CPF</th>
        <th>E-mail</th>
        <th>Telefone</th>
        <th>Vinculo/Profissao</th>
    </tr>
    <?php foreach ($participantes as $participante): ?>
    <tr>
        <td><?php echo htmlspecialchars($participante['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($participante['papel'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $participante['cpf'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $participante['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $participante['telefone'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $participante['vinculo_profissao'], ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
