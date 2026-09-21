<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$logoSrc = logoAtual();
?>
<div class="guest-card">
    <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars('Prêmio de Inovação ' . nomeInstituicao(), ENT_QUOTES, 'UTF-8'); ?>" class="guest-logo">

    <h1 class="guest-titulo">Criar cadastro</h1>
    <p class="guest-subtitulo">Cadastre-se para se inscrever em <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>.</p>

    <?php if (!empty($erro)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo url('eventoInscricao/cadastrar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
        <label>
            Nome
            <input type="text" name="nome" required>
        </label>
        <label>
            E-mail
            <input type="email" name="email" required autocomplete="username">
        </label>
        <label>
            Senha
            <input type="password" name="senha" required autocomplete="new-password">
        </label>
        <button type="submit" class="btn btn-bordered">Cadastrar</button>
    </form>

    <p class="guest-cadastro"><a href="<?php echo url('eventoInscricao/index/' . (int) $evento['id']); ?>">Voltar</a></p>
</div>

<a href="<?php echo config('base_path'); ?>/" class="guest-voltar">&larr; Voltar ao início</a>
<p class="guest-copyright">&copy; <?php echo date('Y'); ?> Poder Judiciário de Roraima</p>
