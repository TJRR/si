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
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <a href="<?php echo url('home/index'); ?>" class="btn">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
        <nav class="site-breadcrumb" aria-label="Navegação estrutural">
            <a href="<?php echo url('home/index'); ?>">Início</a> &gt;
            <a href="<?php echo url('edicoes/index'); ?>">Edições Anteriores</a> &gt;
            <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
        </nav>

        <h1><?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($concurso['descricao'])): ?>
            <p><?php echo nl2br(htmlspecialchars($concurso['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
        <?php endif; ?>

        <p>
            <?php if ($concurso['data_inicio'] !== null || $concurso['data_fim'] !== null): ?>
                Período: <?php echo htmlspecialchars(formatarData($concurso['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a <?php echo htmlspecialchars(formatarData($concurso['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                &nbsp;|&nbsp;
            <?php endif; ?>
            <?php echo (int) $totalEquipes; ?> equipe(s) inscrita(s), <?php echo (int) $totalParticipantes; ?> participante(s)
        </p>

        <?php if (empty($vencedoresPorTrilha)): ?>
            <p><em>Resultados finais não publicados para esta edição.</em></p>
        <?php else: ?>
            <?php foreach ($vencedoresPorTrilha as $grupo): ?>
                <h2 class="section-title"><?php echo htmlspecialchars($grupo['trilha']['nome'], ENT_QUOTES, 'UTF-8'); ?> — Vencedores</h2>
                <div class="site-vencedores-grid">
                    <?php foreach ($grupo['vencedores'] as $vencedor): ?>
                        <div class="admin-card site-premio-card">
                            <strong class="site-premio-posicao"><?php echo (int) $vencedor['colocacao']; ?>º lugar</strong>
                            <p><strong><?php echo htmlspecialchars($vencedor['nome_equipe'] !== null ? $vencedor['nome_equipe'] : 'Equipe #' . $vencedor['equipe_id'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            <?php if (!empty($vencedor['imagem_destaque_path'])): ?>
                                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $vencedor['imagem_destaque_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $vencedor['imagem_destaque_alt'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;border-radius:6px;margin-bottom:.5rem;">
                            <?php endif; ?>
                            <?php if (!empty($vencedor['resumo_destaque'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($vencedor['resumo_destaque'], ENT_QUOTES, 'UTF-8')); ?></p>
                            <?php endif; ?>
                            <?php if ($vencedor['youtube_id'] !== null): ?>
                                <div style="position:relative;width:100%;padding-top:56.25%;">
                                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($vencedor['youtube_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                            style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                                            title="Vídeo de apresentação — <?php echo htmlspecialchars($vencedor['nome_equipe'] !== null ? $vencedor['nome_equipe'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                            allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($documentos)): ?>
            <h2 class="section-title">Documentos</h2>
            <ul>
                <?php foreach ($documentos as $documento): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $documento['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars((isset($rotulosTipoDocumento[$documento['tipo']]) ? $rotulosTipoDocumento[$documento['tipo']] : $documento['tipo']) . ' — ' . $documento['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($galeria)): ?>
            <h2 class="section-title">Galeria</h2>
            <div class="site-galeria-grid">
                <?php foreach ($galeria as $midia): ?>
                    <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $midia['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $midia['alt_text'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
