<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Concursos</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>
<p><a href="<?php echo url('concursos/novo'); ?>">+ Novo concurso</a></p>

<?php if (empty($concursos)): ?>
    <p>Nenhum concurso cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Status</th><th>Periodo</th><th>Acoes</th></tr>
        <?php foreach ($concursos as $concurso): ?>
        <tr>
            <td><?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($concurso['status'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php echo htmlspecialchars((string) $concurso['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars((string) $concurso['data_fim'], ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td>
                <a href="<?php echo url('concursos/editar/' . (int) $concurso['id']); ?>">Editar</a>
                |
                <a href="<?php echo url('trilhas/index/' . (int) $concurso['id']); ?>">Trilhas</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
