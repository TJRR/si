<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$logoConteudo = (new \App\Repositories\ConteudoSiteRepository())->buscarPorChave('logo_site');
$logoSrc = $logoConteudo !== null && !empty($logoConteudo['arquivo_path'])
    ? config('base_path') . '/assets/' . $logoConteudo['arquivo_path']
    : config('base_path') . '/assets/img/logo-padrao.png';
?>
<div class="guest-card">
    <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="guest-logo">

    <h1 class="guest-titulo">Esqueci minha senha</h1>
    <p class="guest-subtitulo">Informe o e-mail da sua conta para receber um link de redefinição de senha.</p>

    <?php if ($enviado): ?>
        <p style="color:green;">Se este e-mail estiver cadastrado e ativo no sistema, enviamos um link de redefinição de senha para ele. Verifique sua caixa de entrada (e a caixa de spam).</p>
    <?php else: ?>
        <form method="post" action="<?php echo url('auth/esqueciSenha'); ?>">
            <label>
                E-mail
                <input type="email" name="email" required autocomplete="username">
            </label>
            <button type="submit" class="btn btn-bordered">Enviar link de redefinição</button>
        </form>
    <?php endif; ?>

    <p class="guest-cadastro"><a href="<?php echo url('auth/login'); ?>">Voltar para o login</a></p>
</div>

<a href="<?php echo config('base_path'); ?>/" class="guest-voltar">&larr; Voltar ao site</a>
<p class="guest-copyright">&copy; <?php echo date('Y'); ?> Poder Judiciário de Roraima</p>
