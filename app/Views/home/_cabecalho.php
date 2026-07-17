<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<header class="site-header" id="cabecalho-site">
    <div class="site-header-inner">
        <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
        <span class="site-edicao-indicador"><?php echo htmlspecialchars($concursoAtivo['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
        <button type="button" class="site-menu-toggle" aria-expanded="false" aria-controls="site-nav-principal" aria-label="Abrir menu">
            <span aria-hidden="true">☰</span>
        </button>
        <nav class="site-nav" id="site-nav-principal">
            <?php foreach ($menu as $item): ?>
                <?php if (!empty($item['externa'])): ?>
                    <a href="<?php echo url($item['url']); ?>"><?php echo htmlspecialchars($item['rotulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php else: ?>
                    <a href="#<?php echo htmlspecialchars($item['ancora'], ENT_QUOTES, 'UTF-8'); ?>" data-scrollspy-alvo="<?php echo htmlspecialchars($item['ancora'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['rotulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?php echo url('auth/login'); ?>" class="btn btn-bordered">Entrar</a>
            <?php if (!empty($trilhasComInscricaoAberta)): ?>
                <?php if (count($trilhasComInscricaoAberta) === 1): ?>
                    <a href="<?php echo url('inscricao/formulario/' . (int) $trilhasComInscricaoAberta[0]['etapa_id']); ?>" class="btn btn-cta">Inscreva-se</a>
                <?php else: ?>
                    <a href="#trilhas" class="btn btn-cta">Inscreva-se</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</header>
