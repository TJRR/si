<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Entrar</h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('auth/login'); ?>">
    <label>E-mail: <input type="email" name="email" required></label><br>
    <label>Senha: <input type="password" name="senha" required></label><br>
    <button type="submit">Entrar</button>
</form>

<p>Ainda nao tem cadastro? <a href="<?php echo url('cadastro/index'); ?>">Cadastre-se</a></p>
