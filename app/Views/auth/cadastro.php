<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Cadastro</h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!empty($sucesso)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('cadastro/index'); ?>">
    <label>Nome: <input type="text" name="nome" required></label><br>
    <label>E-mail: <input type="email" name="email" required></label><br>
    <label>Senha: <input type="password" name="senha" required></label><br>
    <button type="submit">Cadastrar</button>
</form>

<p><a href="<?php echo url('auth/login'); ?>">Voltar para o login</a></p>
