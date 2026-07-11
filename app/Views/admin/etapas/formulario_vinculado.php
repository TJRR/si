<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Formulário vinculado — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if ($formulario === null): ?>
    <p>Esta etapa ainda não tem um formulário dinâmico vinculado.</p>
    <p><a href="<?php echo url('etapas/editar/' . (int) $etapa['id']); ?>">Vincular um formulário em Dados Gerais</a></p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><td><?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><th>Versão</th><td><?php echo (int) $formulario['versao']; ?></td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars($formulario['status'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    </table>

    <p>
        <a href="<?php echo url('campos/index/' . (int) $formulario['id']); ?>">Gerenciar campos</a>
        |
        <a href="<?php echo url('formularios/editar/' . (int) $formulario['id']); ?>">Editar formulário</a>
        <?php if ($formulario['status'] === 'publicado'): ?>
            | <a href="<?php echo (int) $etapa['ordem'] === 1
                ? url('inscricao/formulario/' . (int) $etapa['id'])
                : url('submissao/preencher/' . (int) $etapa['id']); ?>" target="_blank">Ver formulário público</a>
        <?php endif; ?>
    </p>

    <p>
        <?php if (in_array($formulario['status'], ['rascunho', 'despublicado'], true)): ?>
            <form method="post" action="<?php echo url('formularios/publicar'); ?>" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit">Publicar</button>
            </form>
        <?php endif; ?>
        <?php if ($formulario['status'] === 'publicado'): ?>
            <form method="post" action="<?php echo url('formularios/despublicar'); ?>" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit" class="btn-secundario">Despublicar</button>
            </form>
        <?php endif; ?>
        <?php if ($formulario['status'] === 'despublicado'): ?>
            <form method="post" action="<?php echo url('formularios/arquivar'); ?>" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit" class="btn-secundario">Arquivar</button>
            </form>
        <?php endif; ?>
        <?php if ($formulario['status'] === 'arquivado'): ?>
            <form method="post" action="<?php echo url('formularios/desarquivar'); ?>" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit">Desarquivar</button>
            </form>
        <?php endif; ?>
    </p>
<?php endif; ?>
