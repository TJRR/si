<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Etapas pendentes de avaliação</h1>

<?php if (empty($etapas)): ?>
    <p>Nenhuma etapa com avaliação em aberto no momento para os concursos em que você é avaliador.</p>
<?php else: ?>
    <?php $concursoAtual = null; $trilhaAtual = null; ?>
    <?php foreach ($etapas as $etapa): ?>
        <?php if ($etapa['concurso_nome'] !== $concursoAtual): ?>
            <?php if ($trilhaAtual !== null): echo '</table>'; endif; ?>
            <?php $concursoAtual = $etapa['concurso_nome']; $trilhaAtual = null; ?>
            <h2><?php echo htmlspecialchars($concursoAtual, ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php endif; ?>
        <?php if ($etapa['trilha_nome'] !== $trilhaAtual): ?>
            <?php if ($trilhaAtual !== null): echo '</table>'; endif; ?>
            <?php $trilhaAtual = $etapa['trilha_nome']; ?>
            <h3><?php echo htmlspecialchars($trilhaAtual, ENT_QUOTES, 'UTF-8'); ?></h3>
            <table border="1" cellpadding="6">
                <tr><th>Etapa</th><th>Período</th><th>Ações</th></tr>
        <?php endif; ?>
        <tr>
            <td><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if ($etapa['data_inicio'] === null && $etapa['data_fim'] === null): ?>
                    Período não definido
                <?php else: ?>
                    <?php echo htmlspecialchars(formatarData($etapa['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                    a
                    <?php echo htmlspecialchars(formatarData($etapa['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </td>
            <td><a href="<?php echo url('avaliacao/submissoes/' . (int) $etapa['id']); ?>">Ver submissões</a></td>
        </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>
