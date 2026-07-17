<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if ($concursoAtivo === null): ?>
    <div class="site-page">
        <header class="site-header">
            <div class="site-header-inner">
                <img src="<?php echo htmlspecialchars(logoAtual(), ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
                <nav class="site-nav">
                    <a href="<?php echo url('edicoes/index'); ?>">Edições Anteriores</a>
                    <a href="<?php echo url('auth/login'); ?>" class="btn btn-bordered">Entrar</a>
                </nav>
            </div>
        </header>
        <section class="site-section" style="text-align:center;padding:4rem 1rem;">
            <h1>Nenhuma edição ativa no momento</h1>
            <p>Consulte o histórico de <a href="<?php echo url('edicoes/index'); ?>">edições anteriores</a> do Prêmio de Inovação.</p>
        </section>
    </div>
<?php else: ?>
<?php
$logoSrc = !empty($configVisual['logo_path'])
    ? config('base_path') . '/assets/' . $configVisual['logo_path']
    : logoAtual();
$estiloCores = '';

if ($configVisual !== false && $configVisual !== null) {
    $estiloCores = '--cor-primaria-inicio:' . htmlspecialchars($configVisual['cor_primaria_inicio'], ENT_QUOTES, 'UTF-8') . ';'
        . '--cor-primaria-fim:' . htmlspecialchars($configVisual['cor_primaria_fim'], ENT_QUOTES, 'UTF-8') . ';'
        . (!empty($configVisual['cor_secundaria']) ? '--cor-secundaria:' . htmlspecialchars($configVisual['cor_secundaria'], ENT_QUOTES, 'UTF-8') . ';' : '');
}
?>
<div class="site-page" id="topo" style="<?php echo $estiloCores; ?>">
    <a href="#conteudo-principal" class="skip-link">Pular para o conteúdo principal</a>

    <?php include __DIR__ . '/_cabecalho.php'; ?>

    <main id="conteudo-principal">
        <?php if (!empty($_SESSION['flash'])): ?>
            <p class="site-flash"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
        <?php endif; ?>

        <?php include __DIR__ . '/_slideshow.php'; ?>
        <?php include __DIR__ . '/_banners.php'; ?>
        <?php include __DIR__ . '/_trilhas.php'; ?>
        <?php include __DIR__ . '/_sobre.php'; ?>
        <?php include __DIR__ . '/_cronograma.php'; ?>
        <?php include __DIR__ . '/_resultados.php'; ?>
        <?php include __DIR__ . '/_temas.php'; ?>
        <?php include __DIR__ . '/_premiacao.php'; ?>
        <?php foreach ($blocosLivres as $blocoLivre): ?>
            <?php include __DIR__ . '/_bloco_livre.php'; ?>
        <?php endforeach; ?>
        <?php include __DIR__ . '/_faq.php'; ?>
    </main>

    <?php include __DIR__ . '/_rodape.php'; ?>
</div>
<?php endif; ?>
