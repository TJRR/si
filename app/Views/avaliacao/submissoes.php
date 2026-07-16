<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Submissões — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('avaliacao/index'); ?>">Voltar às minhas etapas</a></p>

<?php if (empty($submissoes)): ?>
    <p>Nenhuma submissão designada a você nesta etapa ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th><?php echo $sigiloCego ? 'Submissão' : 'Equipe'; ?></th><th>Status</th><th>Ações</th></tr>
        <?php foreach ($submissoes as $submissao): ?>
        <tr>
            <td>
                <?php if ($sigiloCego): ?>
                    #<?php echo (int) $submissao['id']; ?>
                <?php else: ?>
                    <?php echo htmlspecialchars($submissao['nome_equipe'] !== null ? $submissao['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($submissao['resultado_publicado']): ?>
                    <span class="status-avaliacao completa">Resultado publicado</span>
                <?php elseif ($submissao['criterios_notados'] > 0): ?>
                    <span class="status-avaliacao parcial"><?php echo (int) $submissao['criterios_notados']; ?>/<?php echo (int) $submissao['total_criterios']; ?> critérios</span>
                <?php else: ?>
                    <span class="status-avaliacao pendente">0/<?php echo (int) $submissao['total_criterios']; ?> critérios</span>
                <?php endif; ?>
            </td>
            <td><a href="<?php echo url('avaliacao/notar/' . (int) $submissao['id']); ?>">
                <?php echo $submissao['resultado_publicado'] ? 'Ver notas' : 'Lançar notas'; ?>
            </a></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
