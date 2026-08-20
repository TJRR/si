<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Alterar senha</h1>

<section class="admin-card perfil-card">
    <p class="perfil-aviso">Aplica-se apenas a contas com login por senha. Quem acessa pelo Google não precisa definir senha.</p>

    <form method="post" action="<?php echo url('meuPerfil/alterarSenha'); ?>"><?= campoCsrf() ?>
        <label>Senha atual
            <input type="password" name="senha_atual" required autocomplete="current-password">
        </label>

        <label>Nova senha <span class="perfil-dica-inline">(mín. 8 caracteres)</span>
            <input type="password" name="senha_nova" minlength="8" required autocomplete="new-password">
        </label>

        <label>Confirme a nova senha
            <input type="password" name="confirmacao" minlength="8" required autocomplete="new-password">
        </label>

        <div class="form-acoes">
            <button type="submit">Alterar senha</button>
            <a href="<?php echo url($destinoPainel); ?>" class="btn-voltar">Voltar</a>
        </div>
    </form>
</section>
