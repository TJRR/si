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
                <?php if ($etapa['formulario_dinamico_id'] && $etapa['formulario_status'] === 'publicado'): ?>
                    <?php $urlFormularioPublico = (int) $etapa['ordem'] === 1
                        ? url('inscricao/formulario/' . (int) $etapa['id'])
                        : url('submissao/preencher/' . (int) $etapa['id']); ?>
                    | <a href="<?php echo $urlFormularioPublico; ?>" target="_blank">Ver formulário público</a>
                <?php endif; ?>
                <form method="post" action="<?php echo url('etapas/remover'); ?>" style="display:inline;" onsubmit="return confirm('Remover esta etapa? Só funciona se ela ainda não tiver critérios, notas ou submissões.');">
                    <input type="hidden" name="id" value="<?php echo (int) $etapa['id']; ?>">
                    <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                    <button type="submit" class="btn-secundario">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
