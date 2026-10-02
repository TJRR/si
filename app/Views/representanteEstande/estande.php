<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $rotuloCategoria = isset(\App\Repositories\EstandeRepository::CATEGORIAS[$estande['categoria']]) ? \App\Repositories\EstandeRepository::CATEGORIAS[$estande['categoria']] : ''; ?>
<div class="pagina-titulo-acoes">
    <h1><?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($maisDeUmEstande): ?>
            <a href="<?php echo url('representanteEstande/index'); ?>" class="btn-voltar">Meus estandes</a>
        <?php endif; ?>
    </div>
</div>

<p><?php echo htmlspecialchars($evento !== null ? $evento['nome'] : '', ENT_QUOTES, 'UTF-8'); ?><?php echo $rotuloCategoria !== '' ? ' · ' . htmlspecialchars($rotuloCategoria, ENT_QUOTES, 'UTF-8') : ''; ?></p>

<section class="admin-card">
    <h2>Visitas</h2>
    <p style="font-size:2rem;margin:.25rem 0;"><strong><?php echo (int) $totalVisitas; ?></strong></p>
    <p><?php echo (int) $totalVisitas === 1 ? 'visita registrada' : 'visitas registradas'; ?> pela leitura do código do estande no aplicativo do evento.</p>
    <p>
        <?php if (!empty($estande['ativo'])): ?>
            <span class="status-pill verde">Recebendo visitas</span>
        <?php else: ?>
            <span class="status-pill vermelho">Inativo: não recebe visitas</span>
            <br><small>Só a organização do evento reativa o estande.</small>
        <?php endif; ?>
    </p>
    <p><small>Cada participante registra uma visita por estande e ganha <?php echo (int) $estande['pontos_visita']; ?> <?php echo (int) $estande['pontos_visita'] === 1 ? 'ponto' : 'pontos'; ?> por ela. A pontuação é definida pela organização do evento.</small></p>
</section>

<section class="admin-card">
    <h2>Código do estande</h2>
    <p>Você decide como apresentar o código ao visitante: imprima o cartaz e deixe-o à vista no estande, ou mostre o código na tela do celular ou do computador. O participante lê o QR (ou digita o código) no aplicativo do evento para registrar a visita.</p>
    <p><strong>Código:</strong> <span style="font-family:'Courier New',Courier,monospace;font-size:1.2em;letter-spacing:2px;"><?php echo htmlspecialchars($estande['codigo_estande'], ENT_QUOTES, 'UTF-8'); ?></span></p>
    <p><a href="<?php echo url('representanteEstande/cartaz/' . (int) $estande['id']); ?>" class="btn-acao" target="_blank" rel="noopener">Abrir o cartaz para imprimir</a></p>
    <?php /* Fase 58 (dinâmica de pontos v2: "o responsável pelo estande decide
    como apresentar o QR"): o código também na tela, com a mesma camada de
    ampliação do aplicativo (assets/js/brilho-cracha.js). */ ?>
    <?php $qrEstande = \App\Services\QrCodeService::renderizarSvg($estande['codigo_estande'], 220); ?>
    <p>
        <button type="button" class="btn-acao" data-toggle-brilho="overlay-brilho-estande">Mostrar o código em tela cheia</button>
    </p>
    <div id="overlay-brilho-estande" class="overlay-brilho-qr" hidden data-fechar-overlay-brilho="">
        <?php echo $qrEstande; ?>
    </div>
</section>

<section class="admin-card">
    <h2>Dados que aparecem para os participantes</h2>
    <?php if (!empty($estande['logotipo_path'])): ?>
        <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $estande['logotipo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $estande['logotipo_alt'], ENT_QUOTES, 'UTF-8'); ?>" style="max-width:240px;max-height:120px;display:block;margin:.5rem 0;">
    <?php endif; ?>
    <p><strong><?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php if (!empty($estande['descricao_html'])): ?>
        <div><?php echo sanitizarHtmlRico((string) $estande['descricao_html']); ?></div>
    <?php else: ?>
        <p><em>Sem descrição.</em></p>
    <?php endif; ?>
    <p><a href="<?php echo url('representanteEstande/editar/' . (int) $estande['id']); ?>" class="btn-acao">Atualizar nome, descrição e logotipo</a></p>
</section>

<?php if (!empty($pesquisaAberta)): ?>
    <section class="admin-card">
        <h2>Pesquisa de satisfação</h2>
        <?php if (empty($pesquisaRespondida)): ?>
            <p>A pesquisa de satisfação do evento está aberta, e a sua opinião como representante de estande também conta.</p>
            <p><a href="<?php echo url('eventoApp/pesquisa/' . (int) $estande['evento_id']); ?>" class="btn-acao">Responder à pesquisa</a></p>
        <?php else: ?>
            <p>Obrigado por responder à pesquisa de satisfação.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($inscritoNoEvento): ?>
    <section class="admin-card">
        <p>Você também é inscrito(a) neste evento. <a href="<?php echo url('eventoApp/index/' . (int) $estande['evento_id']); ?>">Abrir o aplicativo do evento</a>.</p>
    </section>
<?php endif; ?>
