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
        <h1>Equipes homologadas — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (empty($equipes)): ?>
            <p><em>Nenhuma equipe homologada nesta trilha ainda.</em></p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1.5em; max-width:480px;">
                <?php foreach ($equipes as $equipe): ?>
                    <div>
                        <h3><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <ul>
                            <?php foreach ($equipe['integrantes'] as $integrante): ?>
                                <li>
                                    <?php echo htmlspecialchars($integrante['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php echo $integrante['papel'] === 'lider' ? ' (líder)' : ''; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
