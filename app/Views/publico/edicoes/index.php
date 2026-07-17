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
        <nav class="site-breadcrumb" aria-label="Navegação estrutural">
            <a href="<?php echo url('home/index'); ?>">Início</a> &gt; Edições Anteriores
        </nav>

        <h1>Edições Anteriores</h1>

        <?php if (empty($edicoes)): ?>
            <p>Nenhuma edição anterior cadastrada ainda.</p>
        <?php else: ?>
            <div class="site-edicoes-grid">
                <?php foreach ($edicoes as $edicao): ?>
                    <a href="<?php echo url('edicoes/detalhe/' . $edicao['slug']); ?>" class="admin-card site-edicao-card">
                        <h2 class="section-title"><?php echo htmlspecialchars($edicao['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <?php if ($edicao['data_inicio'] !== null || $edicao['data_fim'] !== null): ?>
                            <p>
                                <?php echo htmlspecialchars(formatarData($edicao['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                                a
                                <?php echo htmlspecialchars(formatarData($edicao['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($edicao['descricao'])): ?>
                            <p><?php echo htmlspecialchars($edicao['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
