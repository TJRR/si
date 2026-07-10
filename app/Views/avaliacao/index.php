<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Avaliação — minhas etapas</h1>

<?php if (empty($etapas)): ?>
    <p>Nenhuma etapa com avaliação em aberto no momento para os concursos em que você é avaliador.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Concurso</th><th>Trilha</th><th>Etapa</th><th>Período</th><th>Ações</th></tr>
        <?php foreach ($etapas as $etapa): ?>
        <tr>
            <td><?php echo htmlspecialchars($etapa['concurso_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($etapa['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php echo htmlspecialchars((string) $etapa['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars((string) $etapa['data_fim'], ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td><a href="<?php echo url('avaliacao/submissoes/' . (int) $etapa['id']); ?>">Ver submissões</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
