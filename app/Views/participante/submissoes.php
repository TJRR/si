<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Submissões</h1>

<p>
    <a href="<?php echo url('participante/meusDados'); ?>">Meus dados</a>
    |
    <a href="<?php echo url('participante/minhaEquipe'); ?>">Minha equipe</a>
</p>

<?php if (!$homologado): ?>
    <p>Sua inscrição ainda não foi homologada — assim que for, as etapas de submissão aparecerão aqui.</p>
<?php elseif (empty($etapas)): ?>
    <p>Nenhuma etapa de submissão disponível no momento para a sua trilha.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Etapa</th><th>Período</th><th>Ação</th></tr>
        <?php foreach ($etapas as $etapa): ?>
        <tr>
            <td><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php echo htmlspecialchars((string) $etapa['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars((string) $etapa['data_fim'], ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td><a href="<?php echo url('submissao/preencher/' . (int) $etapa['id']); ?>">Preencher</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
