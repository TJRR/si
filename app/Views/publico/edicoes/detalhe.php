<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$rotulosTipoDocumento = [
    'edital' => 'Edital', 'edital_simples' => 'Edital em linguagem simples', 'anexo' => 'Anexo',
    'retificacao' => 'Retificação', 'resultado_final' => 'Resultado final', 'ata' => 'Ata',
];
?>
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
            <nav class="site-breadcrumb" aria-label="Navegação estrutural">
                <a href="<?php echo url('home/index'); ?>">Início</a> &gt;
                <a href="<?php echo url('edicoes/index'); ?>">Edições Anteriores</a> &gt;
                <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
            </nav>
            <h1><?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>
                <?php if ($concurso['data_inicio'] !== null || $concurso['data_fim'] !== null): ?>
                    Período: <?php echo htmlspecialchars(formatarData($concurso['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a <?php echo htmlspecialchars(formatarData($concurso['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                    &nbsp;|&nbsp;
                <?php endif; ?>
                <?php echo (int) $totalEquipes; ?> equipe(s) inscrita(s), <?php echo (int) $totalParticipantes; ?> participante(s)
            </p>
        </div>
    </div>

    <?php if (!empty($concurso['descricao'])): ?>
    <div class="site-secao-publica">
        <div class="site-secao-larga">
            <div class="section-text"><?php echo $concurso['descricao']; ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($vencedoresPorTrilha)): ?>
    <div class="site-secao-publica site-secao-publica-alt">
        <div class="site-secao-larga">
            <p><em>Resultados finais não publicados para esta edição.</em></p>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($vencedoresPorTrilha as $indiceGrupo => $grupo): ?>
        <div class="site-secao-publica <?php echo $indiceGrupo % 2 === 0 ? 'site-secao-publica-alt' : ''; ?>">
            <div class="site-secao-larga">
                <h2 class="section-title"><?php echo htmlspecialchars($grupo['trilha']['nome'], ENT_QUOTES, 'UTF-8'); ?>: Vencedores</h2>
                <div class="site-vencedores-grid">
                    <?php foreach ($grupo['vencedores'] as $vencedor): ?>
                        <div class="admin-card site-premio-card">
                            <strong class="site-premio-posicao"><?php echo (int) $vencedor['colocacao']; ?>º lugar</strong>
                            <p><strong><?php echo htmlspecialchars($vencedor['nome_equipe'] !== null ? $vencedor['nome_equipe'] : 'Equipe #' . $vencedor['equipe_id'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            <?php if (!empty($vencedor['imagem_destaque_path'])): ?>
                                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $vencedor['imagem_destaque_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $vencedor['imagem_destaque_alt'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;border-radius:6px;margin-bottom:.5rem;">
                            <?php endif; ?>
                            <?php if (!empty($vencedor['resumo_destaque'])): ?>
                                <div class="site-premio-resumo"><?php echo $vencedor['resumo_destaque']; ?></div>
                            <?php endif; ?>
                            <?php if ($vencedor['youtube_id'] !== null): ?>
                                <div style="position:relative;width:100%;padding-top:56.25%;">
                                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($vencedor['youtube_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                            style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                                            title="Vídeo de apresentação: <?php echo htmlspecialchars($vencedor['nome_equipe'] !== null ? $vencedor['nome_equipe'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                            allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($documentos)): ?>
    <div class="site-secao-publica">
        <div class="site-secao-larga">
            <h2 class="section-title">Documentos</h2>
            <ul class="site-documentos-lista">
                <?php foreach ($documentos as $documento): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $documento['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars((isset($rotulosTipoDocumento[$documento['tipo']]) ? $rotulosTipoDocumento[$documento['tipo']] : $documento['tipo']) . ': ' . $documento['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($galeria)): ?>
    <div class="site-secao-publica site-secao-publica-alt">
        <div class="site-secao-larga">
            <h2 class="section-title">Galeria</h2>
            <div class="site-galeria-grid">
                <?php foreach ($galeria as $midia): ?>
                    <?php
                    $urlMidia = config('base_path') . '/assets/' . $midia['arquivo_path'];
                    $legendaMidia = !empty($midia['titulo']) ? $midia['titulo'] : (!empty($midia['descricao']) ? $midia['descricao'] : '');
                    ?>
                    <button type="button" class="site-galeria-item"
                            data-lightbox-src="<?php echo htmlspecialchars($urlMidia, ENT_QUOTES, 'UTF-8'); ?>"
                            data-lightbox-alt="<?php echo htmlspecialchars((string) $midia['alt_text'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-lightbox-legenda="<?php echo htmlspecialchars($legendaMidia, ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars($urlMidia, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $midia['alt_text'], ENT_QUOTES, 'UTF-8'); ?>">
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="lightbox-galeria" class="lightbox-overlay" hidden>
        <button type="button" class="lightbox-fechar" aria-label="Fechar">&times;</button>
        <button type="button" class="lightbox-seta lightbox-anterior" aria-label="Foto anterior">&lsaquo;</button>
        <figure class="lightbox-figura">
            <img id="lightbox-imagem" src="" alt="">
            <figcaption id="lightbox-legenda" class="lightbox-legenda"></figcaption>
        </figure>
        <button type="button" class="lightbox-seta lightbox-proxima" aria-label="Próxima foto">&rsaquo;</button>
    </div>
    <?php endif; ?>

    <?php if (!empty($blocoConcurso) && !empty($blocoConcurso['ativo']) && !empty($blocoConcurso['conteudo_html'])): ?>
    <div class="site-secao-publica">
        <div class="site-secao-larga">
            <?php if (!empty($blocoConcurso['titulo'])): ?>
            <h2 class="section-title"><?php echo htmlspecialchars($blocoConcurso['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php endif; ?>
            <div class="section-text"><?php echo $blocoConcurso['conteudo_html']; ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>
