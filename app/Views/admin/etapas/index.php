<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Etapas de <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>
<p><a href="<?php echo url('etapas/novo/' . (int) $trilha['id']); ?>">+ Nova etapa</a></p>

<?php if (empty($etapas)): ?>
    <p>Nenhuma etapa cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Ordem</th><th>Período</th><th>Regra de transição</th><th>Formulário vinculado</th><th>Ações</th></tr>
        <?php foreach ($etapas as $etapa): ?>
        <tr>
            <td><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $etapa['ordem']; ?></td>
            <td>
                <?php echo htmlspecialchars((string) $etapa['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars((string) $etapa['data_fim'], ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td><?php echo $etapa['regra_transicao_tipo'] !== null ? htmlspecialchars($etapa['regra_transicao_tipo'] . ': ' . $etapa['regra_transicao_valor'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
            <td><?php echo $etapa['formulario_dinamico_id'] ? '#' . (int) $etapa['formulario_dinamico_id'] : '—'; ?></td>
            <td>
                <a href="<?php echo url('etapas/editar/' . (int) $etapa['id']); ?>">Editar</a>
                | <a href="<?php echo url('criterios/index/' . (int) $etapa['id']); ?>">Critérios</a>
                | <a href="<?php echo url('formulas/etapa/' . (int) $etapa['id']); ?>">Fórmula</a>
                | <a href="<?php echo url('designacoes/index/' . (int) $etapa['id']); ?>">Designações</a>
                | <a href="<?php echo url('resultados/etapa/' . (int) $etapa['id']); ?>">Resultado</a>
                <?php if ($etapa['formulario_dinamico_id']): ?>
                    | <a href="<?php echo url('submissao/preencher/' . (int) $etapa['id']); ?>" target="_blank">Ver formulário público</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
