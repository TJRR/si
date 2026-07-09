<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Equipes importadas</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>
<p><a href="<?php echo url('revisao/index'); ?>">Conferencia de inscricoes importadas</a></p>

<?php if (empty($equipes)): ?>
    <p>Nenhuma equipe importada ainda.</p>
<?php else: ?>
    <p><?php echo count($equipes); ?> equipe(s) importada(s).</p>
    <table border="1" cellpadding="6">
        <tr>
            <th>Equipe</th>
            <th>Trilha</th>
            <th>Participantes</th>
            <th>Importado em</th>
            <th>Acoes</th>
        </tr>
        <?php foreach ($equipes as $equipe): ?>
        <tr>
            <td><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($equipe['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $equipe['total_participantes']; ?></td>
            <td><?php echo htmlspecialchars((string) $equipe['importado_em'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><a href="<?php echo url('revisao/equipe/' . (int) $equipe['id']); ?>">Ver participantes</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
