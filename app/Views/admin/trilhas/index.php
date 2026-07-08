<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Trilhas de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('concursos/index'); ?>">Voltar aos concursos</a></p>
<p><a href="<?php echo url('trilhas/novo/' . (int) $concurso['id']); ?>">+ Nova trilha</a></p>

<?php if (empty($trilhas)): ?>
    <p>Nenhuma trilha cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Ordem</th><th>Ativo</th><th>Acoes</th></tr>
        <?php foreach ($trilhas as $trilha): ?>
        <tr>
            <td><?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $trilha['ordem']; ?></td>
            <td><?php echo $trilha['ativo'] ? 'Sim' : 'Nao'; ?></td>
            <td>
                <a href="<?php echo url('trilhas/editar/' . (int) $trilha['id']); ?>">Editar</a>
                |
                <a href="<?php echo url('temas/index/' . (int) $trilha['id']); ?>">Temas/Desafios</a>
                |
                <a href="<?php echo url('etapas/index/' . (int) $trilha['id']); ?>">Etapas</a>
                |
                <a href="<?php echo url('formulas/trilha/' . (int) $trilha['id']); ?>">Formula da nota final</a>
                |
                <a href="<?php echo url('desempate/index/' . (int) $trilha['id']); ?>">Desempate</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
