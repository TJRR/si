<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Submissões — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('avaliacao/index'); ?>">Voltar às minhas etapas</a></p>

<?php if ($sigiloCego): ?>
    <p><em>Esta etapa é de avaliação cega: os dados da equipe/participantes ficam ocultos, você vê apenas o número da submissão e o tema.</em></p>
<?php endif; ?>

<?php if (empty($submissoes)): ?>
    <p>Nenhuma submissão designada a você nesta etapa ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Submissão</th><?php if (!$sigiloCego): ?><th>Equipe</th><?php endif; ?><th>Sua avaliação</th><th>Ações</th></tr>
        <?php foreach ($submissoes as $submissao): ?>
        <tr>
            <td>#<?php echo (int) $submissao['id']; ?></td>
            <?php if (!$sigiloCego): ?>
                <td><?php echo htmlspecialchars($submissao['nome_equipe'] !== null ? $submissao['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <?php endif; ?>
            <td>
                <?php if ($submissao['resultado_publicado']): ?>
                    Resultado já publicado
                <?php elseif ($submissao['avaliacao_completa']): ?>
                    Completa
                <?php else: ?>
                    Pendente
                <?php endif; ?>
            </td>
            <td><a href="<?php echo url('avaliacao/notar/' . (int) $submissao['id']); ?>">
                <?php echo $submissao['resultado_publicado'] ? 'Ver notas' : 'Lançar notas'; ?>
            </a></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
