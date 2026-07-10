<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Convidar usuário</h1>

<p><a href="<?php echo url('usuarios/index'); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('usuarios/convidar'); ?>">
    <label>Nome:
        <input type="text" name="nome" required>
    </label><br>

    <label>E-mail:
        <input type="email" name="email" required>
    </label><br>

    <label>Perfil:
        <select name="perfil" required>
            <option value="">Perfil...</option>
            <?php foreach ($perfis as $perfil): ?>
                <option value="<?php echo htmlspecialchars($perfil['chave'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($perfil['nome_exibicao'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Concurso:
        <select name="concurso_id">
            <option value="">Global (todos os concursos)</option>
            <?php foreach ($concursos as $concurso): ?>
                <option value="<?php echo (int) $concurso['id']; ?>">
                    <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <p>Se o e-mail já tiver cadastro, só o perfil escolhido acima é adicionado — nenhum e-mail novo é enviado.</p>

    <button type="submit">Convidar</button>
</form>
