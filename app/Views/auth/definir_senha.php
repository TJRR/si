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
    <?php if (isset($ajudaHtml) && $ajudaHtml !== null): ?>
    <button type="button" class="guest-ajuda-botao" title="Ajuda desta tela" aria-label="Ajuda desta tela" data-ajuda-titulo="<?php echo htmlspecialchars('Ajuda — ' . (string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModal(this.dataset.ajudaTitulo, document.getElementById('ajuda-painel-fonte').innerHTML)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
    </button>
    <?php endif; ?>
    <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="guest-logo">

    <h1 class="guest-titulo">Definir senha</h1>
    <p class="guest-subtitulo">Escolha uma senha para acessar o sistema com e-mail e senha (você também pode sempre usar "Entrar com Google").</p>

    <?php if (!empty($erro)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if ($token !== null): ?>
        <form method="post" action="<?php echo url('auth/definirSenha/' . $token); ?>"><?= campoCsrf() ?>
            <label>
                Nova senha
                <input type="password" name="senha" required minlength="8" autocomplete="new-password">
            </label>
            <label>
                Confirme a senha
                <input type="password" name="confirmacao" required minlength="8" autocomplete="new-password">
            </label>
            <button type="submit" class="btn btn-bordered">Salvar senha</button>
        </form>
    <?php endif; ?>
</div>

<a href="<?php echo config('base_path'); ?>/" class="guest-voltar">&larr; Voltar ao site</a>
