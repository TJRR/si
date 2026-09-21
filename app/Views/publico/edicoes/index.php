<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars('Prêmio de Inovação ' . nomeInstituicao(), ENT_QUOTES, 'UTF-8'); ?>" class="site-logo">
            <nav class="site-nav">
                <?php if (isset($ajudaHtml) && $ajudaHtml !== null): ?>
                <button type="button" class="site-header-icone" title="Ajuda desta página" aria-label="Ajuda desta página" data-ajuda-titulo="<?php echo htmlspecialchars('Ajuda: ' . (string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModal(this.dataset.ajudaTitulo, document.getElementById('ajuda-painel-fonte').innerHTML)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </button>
                <?php endif; ?>
                <a href="<?php echo url('home/index'); ?>" class="btn">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-hero-simples">
        <div class="site-secao-larga">
            <h1>Edições Anteriores</h1>
        </div>
    </div>

    <div class="site-secao-publica site-secao-publica-alt">
        <div class="site-secao-larga">
            <?php if (empty($edicoes)): ?>
                <p>Nenhuma edição anterior cadastrada ainda.</p>
            <?php else: ?>
                <div class="site-edicoes-grid">
                    <?php foreach ($edicoes as $edicao): ?>
                        <?php preg_match('/^(\d+)/', $edicao['nome'], $numeroEdicaoMatch); ?>
                        <a href="<?php echo url('edicoes/detalhe/' . $edicao['slug']); ?>" class="site-edicao-card">
                            <?php if (!empty($edicao['imagem_capa'])): ?>
                                <div class="site-edicao-capa" style="background-image:url('<?php echo htmlspecialchars(config('base_path') . '/assets/' . $edicao['imagem_capa'], ENT_QUOTES, 'UTF-8'); ?>');" role="img" aria-label="<?php echo htmlspecialchars((string) $edicao['imagem_capa_alt'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                            <?php else: ?>
                                <div class="site-edicao-capa site-edicao-capa-vazia">
                                    <span><?php echo htmlspecialchars(isset($numeroEdicaoMatch[1]) ? $numeroEdicaoMatch[1] : '', ENT_QUOTES, 'UTF-8'); ?>º</span>
                                </div>
                            <?php endif; ?>
                            <div class="site-edicao-card-corpo">
                                <h2 class="section-title"><?php echo htmlspecialchars($edicao['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <?php if ($edicao['data_inicio'] !== null || $edicao['data_fim'] !== null): ?>
                                    <p class="site-edicao-periodo">
                                        <?php echo htmlspecialchars(formatarData($edicao['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                                        a
                                        <?php echo htmlspecialchars(formatarData($edicao['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
