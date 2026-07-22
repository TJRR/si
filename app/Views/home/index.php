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
$temImagemCabecalho = !empty($configVisual['cabecalho_imagem_path']);
$urlImagemCabecalho = $temImagemCabecalho ? config('base_path') . '/assets/' . $configVisual['cabecalho_imagem_path'] : null;
$logoClaroSrc = !empty($configVisual['cabecalho_logo_claro_path']) ? config('base_path') . '/assets/' . $configVisual['cabecalho_logo_claro_path'] : null;
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
    <?php include __DIR__ . '/_painel_cronograma.php'; ?>

    <main id="conteudo-principal">
        <?php if (!empty($_SESSION['flash'])): ?>
            <p class="site-flash"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
        <?php endif; ?>

        <?php include __DIR__ . '/_slideshow.php'; ?>
        <?php include __DIR__ . '/_banners.php'; ?>
        <?php
        // Fase 19 (#97): ordem definida pelo Admin (aba "Ordenação") -
        // cada partial ja se auto-esconde quando nao tem dado
        // (if (empty(...))/if ($bloco !== null) ja existentes em cada
        // um), entao so' precisamos incluir na ordem certa.
        $indiceAlternado = 0;
        ?>
        <?php foreach ($secoesOrdenadas as $secao): ?>
            <?php if ($secao['tipo'] === 'fixa'): ?>
                <?php include __DIR__ . '/_' . $secao['chave_fixa'] . '.php'; ?>
            <?php elseif ($secao['bloco_chave'] === 'sobre'): ?>
                <?php include __DIR__ . '/_sobre.php'; ?>
            <?php elseif ($secao['bloco_chave'] === 'premiacao'): ?>
                <?php include __DIR__ . '/_premiacao.php'; ?>
            <?php elseif (isset($blocosPorId[$secao['bloco_id']])): ?>
                <?php $blocoLivre = $blocosPorId[$secao['bloco_id']]; ?>
                <?php $alternado = $indiceAlternado % 2 === 1; $indiceAlternado++; ?>
                <?php include __DIR__ . '/_bloco_livre.php'; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </main>

    <?php include __DIR__ . '/_rodape.php'; ?>
</div>
<?php endif; ?>
