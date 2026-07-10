<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Designação de avaliadores — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $trilha['id']); ?>">Voltar às etapas</a></p>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p>Modo de designação desta etapa: <strong><?php
    echo [
        'manual' => 'Admin atribui manualmente',
        'aberto' => 'Todo avaliador da trilha vê tudo (não precisa designar)',
        'automatico' => 'Distribuição automática balanceada',
    ][$etapa['modo_designacao']] ?? 'Não definido — edite a etapa e configure antes de designar';
?></strong></p>

<?php if ($etapa['modo_designacao'] === 'aberto'): ?>
    <p>Como o modo é "aberto", qualquer avaliador vinculado a este concurso já pode notar todas as submissões desta etapa — não há necessidade de designar manualmente.</p>
<?php else: ?>

    <?php if ($etapa['modo_designacao'] === 'automatico'): ?>
        <form method="post" action="<?php echo url('designacoes/distribuir/' . (int) $etapa['id']); ?>">
            <button type="submit">Distribuir automaticamente (avaliadores faltantes)</button>
        </form>
    <?php endif; ?>

    <?php if (empty($avaliadores)): ?>
        <p><strong>Nenhum avaliador vinculado a este concurso ainda.</strong> Aprove um cadastro com perfil "Avaliador" em Cadastros pendentes antes de designar.</p>
    <?php endif; ?>

    <table border="1" cellpadding="6">
        <tr><th>Submissão</th><th>Equipe</th><th>Avaliadores designados</th><th>Atribuir avaliador</th></tr>
        <?php foreach ($submissoes as $submissao): ?>
        <tr>
            <td>#<?php echo (int) $submissao['id']; ?></td>
            <td><?php echo htmlspecialchars($submissao['nome_equipe'] !== null ? $submissao['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if (empty($submissao['designacoes'])): ?>
                    Nenhum
                <?php else: ?>
                    <?php foreach ($submissao['designacoes'] as $designacao): ?>
                        <form method="post" action="<?php echo url('designacoes/remover'); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $designacao['id']; ?>">
                            <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                            <?php echo htmlspecialchars($designacao['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>
                            <button type="submit">Remover</button>
                        </form><br>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($avaliadores)): ?>
                <form method="post" action="<?php echo url('designacoes/atribuir'); ?>">
                    <input type="hidden" name="submissao_id" value="<?php echo (int) $submissao['id']; ?>">
                    <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                    <select name="usuario_id">
                        <?php foreach ($avaliadores as $avaliador): ?>
                            <option value="<?php echo (int) $avaliador['id']; ?>">
                                <?php echo htmlspecialchars($avaliador['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Designar</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
