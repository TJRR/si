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

    <h1 class="guest-titulo">Bem-vindo de volta</h1>
    <p class="guest-subtitulo">Acesse o painel do Prêmio de Inovação do TJRR.</p>

    <?php if (!empty($erro)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <a href="<?php echo url('auth/google'); ?>" class="guest-google">
        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Entrar com Google
    </a>

    <div class="guest-divisor">ou acesse com e-mail</div>

    <form method="post" action="<?php echo url('auth/login'); ?>">
        <label>
            E-mail
            <input type="email" name="email" required autocomplete="username">
        </label>
        <label>
            Senha
            <input type="password" name="senha" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-bordered">Entrar</button>
    </form>

    <p class="guest-cadastro">Ainda não tem cadastro? <a href="<?php echo url('cadastro/index'); ?>">Cadastre-se</a></p>
</div>

<a href="<?php echo config('base_path'); ?>/" class="guest-voltar">&larr; Voltar ao site</a>
<p class="guest-copyright">&copy; <?php echo date('Y'); ?> Poder Judiciário de Roraima</p>
