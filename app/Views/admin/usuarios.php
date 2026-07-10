<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Cadastros pendentes</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>

<?php if (empty($pendentes)): ?>
    <p>Nenhum cadastro pendente.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>E-mail</th><th>Ações</th></tr>
        <?php foreach ($pendentes as $usuario): ?>
        <tr>
            <td><?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('usuarios/aprovar'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                    <select name="perfil" required>
                        <option value="">Perfil...</option>
                        <?php foreach ($perfis as $perfil): ?>
                            <option value="<?php echo htmlspecialchars($perfil['chave'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($perfil['nome_exibicao'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="concurso_id">
                        <option value="">Global (todos os concursos)</option>
                        <?php foreach ($concursos as $concurso): ?>
                            <option value="<?php echo (int) $concurso['id']; ?>">
                                <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Aprovar</button>
                </form>
                <form method="post" action="<?php echo url('usuarios/rejeitar'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                    <button type="submit">Rejeitar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
