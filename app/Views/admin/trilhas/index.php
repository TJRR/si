<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Trilhas de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('concursos/index'); ?>">Voltar aos concursos</a></p>
<p><a href="<?php echo url('trilhas/novo/' . (int) $concurso['id']); ?>">+ Nova trilha</a>
| <a href="<?php echo url('formularios/index/' . (int) $concurso['id']); ?>">Formulários deste concurso</a></p>

<?php if (empty($trilhas)): ?>
    <p>Nenhuma trilha cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Ordem</th><th>Ativo</th><th>Inscrições</th><th>Ações</th></tr>
        <?php foreach ($trilhas as $trilha): ?>
        <tr>
            <td><?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $trilha['ordem']; ?></td>
            <td><?php echo $trilha['ativo'] ? 'Sim' : 'Não'; ?></td>
            <td>
                <?php if ($trilha['etapa_cadastro_id'] === null): ?>
                    <em>sem etapa de cadastro</em>
                <?php else: ?>
                    <strong><?php echo $trilha['inscricoes_abertas'] ? 'Abertas' : 'Fechadas'; ?></strong>
                    <form method="post" action="<?php echo url('trilhas/alternarInscricoes/' . (int) $trilha['id']); ?>" style="display:inline;">
                        <button type="submit" class="<?php echo $trilha['inscricoes_abertas'] ? 'btn-secundario' : ''; ?>">
                            <?php echo $trilha['inscricoes_abertas'] ? 'Fechar' : 'Abrir'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?php echo url('trilhas/editar/' . (int) $trilha['id']); ?>">Editar</a>
                <form method="post" action="<?php echo url('trilhas/remover'); ?>" style="display:inline;" onsubmit="return confirm('Remover esta trilha? Só funciona se ela ainda não tiver etapas, equipes ou outros dados vinculados.');">
                    <input type="hidden" name="id" value="<?php echo (int) $trilha['id']; ?>">
                    <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
                    <button type="submit" class="btn-secundario">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
