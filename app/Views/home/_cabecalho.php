<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$efeitoTransicaoCabecalho = !empty($configVisual['cabecalho_efeito_transicao']) ? $configVisual['cabecalho_efeito_transicao'] : 'onda';
$overlayOpacidadeCabecalho = isset($configVisual['cabecalho_overlay_opacidade']) ? ((int) $configVisual['cabecalho_overlay_opacidade']) / 100 : 0.5;
$mapaPosicoesCabecalho = [
    'superior_esquerda' => 'left top', 'superior_centro' => 'center top', 'superior_direita' => 'right top',
    'centro_esquerda' => 'left center', 'centro_centro' => 'center center', 'centro_direita' => 'right center',
    'inferior_esquerda' => 'left bottom', 'inferior_centro' => 'center bottom', 'inferior_direita' => 'right bottom',
];
$posicaoChaveCabecalho = !empty($configVisual['cabecalho_imagem_posicao']) ? $configVisual['cabecalho_imagem_posicao'] : 'superior_centro';
$posicaoImagemCabecalho = $mapaPosicoesCabecalho[$posicaoChaveCabecalho] ?? 'center top';
?>
<header class="site-header<?php echo $temImagemCabecalho ? ' site-header-com-imagem' : ''; ?>" id="cabecalho-site"
        <?php if ($temImagemCabecalho): ?>style="background-image:url('<?php echo htmlspecialchars($urlImagemCabecalho, ENT_QUOTES, 'UTF-8'); ?>');background-position:<?php echo $posicaoImagemCabecalho; ?>;--cabecalho-overlay-opacidade:<?php echo $overlayOpacidadeCabecalho; ?>;"<?php endif; ?>>
    <div class="site-header-nav" id="site-header-nav">
        <div class="site-header-inner">
            <?php if ($temImagemCabecalho && $logoClaroSrc !== null): ?>
                <img src="<?php echo htmlspecialchars($logoClaroSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo site-logo-claro">
            <?php endif; ?>
            <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo site-logo-solido">
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
                <button type="button" class="site-header-icone" data-abrir-painel-cronograma aria-label="Ver cronograma" title="Ver cronograma">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </button>
                <a href="<?php echo url('auth/login'); ?>" class="btn btn-bordered">Entrar</a>
            </nav>
        </div>
    </div>
    <?php if ($temImagemCabecalho && !empty($configVisual['cabecalho_titulo_html'])): ?>
        <div class="site-header-hero-conteudo">
            <div class="site-header-hero-texto"><?php echo $configVisual['cabecalho_titulo_html']; ?></div>
        </div>
    <?php endif; ?>
    <?php if ($temImagemCabecalho): ?>
        <?php if ($efeitoTransicaoCabecalho === 'onda'): ?>
            <div class="site-header-shape-bottom" aria-hidden="true">
                <svg viewBox="0 0 1000 100" preserveAspectRatio="none">
                    <path d="M421.9,6.5c22.6-2.5,51.5,0.4,75.5,5.3c23.6,4.9,70.9,23.5,100.5,35.7c75.8,32.2,133.7,44.5,192.6,49.7
                        c23.6,2.1,48.7,3.5,103.4-2.5c54.7-6,106.2-25.6,106.2-25.6V0H0v30.3c0,0,72,32.6,158.4,30.5c39.2-0.7,92.8-6.7,134-22.4
                        c21.2-8.1,52.2-18.2,79.7-24.2C399.3,7.9,411.6,7.5,421.9,6.5z"></path>
                </svg>
            </div>
        <?php elseif ($efeitoTransicaoCabecalho === 'diagonal_esquerda'): ?>
            <div class="site-header-shape-diagonal site-header-shape-diagonal-esquerda" aria-hidden="true"></div>
        <?php elseif ($efeitoTransicaoCabecalho === 'diagonal_direita'): ?>
            <div class="site-header-shape-diagonal site-header-shape-diagonal-direita" aria-hidden="true"></div>
        <?php endif; ?>
        <span class="site-header-sentinela" aria-hidden="true"></span>
    <?php endif; ?>
</header>
