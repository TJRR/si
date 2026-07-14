<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <a href="<?php echo url('home/index'); ?>" class="btn">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
        <h1>Inscrição enviada com sucesso</h1>
        <p>A equipe <strong><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></strong> foi inscrita.</p>
        <p>Sua inscrição passará por uma conferência (homologação). Assim que for homologada, você receberá um e-mail com instruções de acesso ao sistema.</p>
    </div>
</div>
