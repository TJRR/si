<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <a href="<?php echo url('home/index'); ?>">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
        <h1>Equipes classificadas — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (empty($equipes)): ?>
            <p><em>Nenhuma equipe classificada nesta etapa.</em></p>
        <?php else: ?>
            <div style="display:flex; flex-wrap:wrap; gap:1.5em;">
                <?php foreach ($equipes as $equipe): ?>
                    <div style="width:320px;">
                        <h3><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if ($equipe['youtube_id'] !== null): ?>
                            <div style="position:relative; width:100%; padding-top:56.25%;">
                                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($equipe['youtube_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                        allowfullscreen></iframe>
                            </div>
                        <?php else: ?>
                            <p><em>Sem vídeo disponível.</em></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
