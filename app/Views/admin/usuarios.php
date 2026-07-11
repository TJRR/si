<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Usuários</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>
<p><a href="<?php echo url('usuarios/convidar'); ?>">+ Convidar usuário</a></p>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="get" action="<?php echo config('base_path'); ?>/index.php">
    <input type="hidden" name="r" value="usuarios/index">
    <label>Filtrar por concurso:
        <select name="concurso_id">
            <option value="">Todos</option>
            <?php foreach ($concursos as $concurso): ?>
                <option value="<?php echo (int) $concurso['id']; ?>" <?php echo $filtroConcursoId === (int) $concurso['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Filtrar</button>
</form>

<?php if (empty($usuarios)): ?>
    <p>Nenhum usuário encontrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>E-mail</th><th>Status</th><th>Perfis</th><th>Acesso</th><th>Ações</th></tr>
        <?php foreach ($usuarios as $usuario): ?>
        <tr>
            <td><?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php echo htmlspecialchars(ucfirst($usuario['status']), ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!$usuario['ativo']): ?>
                    <br><strong>Suspenso</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if (empty($usuario['perfis'])): ?>
                    —
                <?php else: ?>
                    <?php foreach ($usuario['perfis'] as $vinculo): ?>
                        <?php echo htmlspecialchars($vinculo['perfil_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        (<?php echo htmlspecialchars($vinculo['concurso_nome'] !== null ? $vinculo['concurso_nome'] : 'Global', ENT_QUOTES, 'UTF-8'); ?>)<br>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <td>
                <?php
                    $tiposAcesso = [];
                    if ($usuario['senha_hash'] !== null) {
                        $tiposAcesso[] = 'Senha';
                    }
                    if ($usuario['google_id'] !== null) {
                        $tiposAcesso[] = 'Google';
                    }
                    echo htmlspecialchars(!empty($tiposAcesso) ? implode(' + ', $tiposAcesso) : 'Nenhum ainda', ENT_QUOTES, 'UTF-8');
                ?>
            </td>
            <td>
                <?php if ($usuario['status'] === 'pendente'): ?>
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
                <?php endif; ?>

                <?php if ($usuario['ativo']): ?>
                    <form method="post" action="<?php echo url('usuarios/suspender'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                        <button type="submit" class="btn-secundario" onclick="return confirm('Suspender este usuário? Ele não conseguirá mais fazer login.');">Suspender</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?php echo url('usuarios/reativar'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                        <button type="submit">Reativar</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
