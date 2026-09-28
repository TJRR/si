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
    <h2>Cartaz com o código</h2>
    <p>Imprima o cartaz e deixe-o à vista no estande: o participante lê o QR (ou digita o código) no aplicativo do evento para registrar a visita.</p>
    <p><strong>Código:</strong> <span style="font-family:'Courier New',Courier,monospace;font-size:1.2em;letter-spacing:2px;"><?php echo htmlspecialchars($estande['codigo_estande'], ENT_QUOTES, 'UTF-8'); ?></span></p>
    <p><a href="<?php echo url('representanteEstande/cartaz/' . (int) $estande['id']); ?>" class="btn-acao" target="_blank" rel="noopener">Abrir o cartaz para imprimir</a></p>
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

<?php if ($inscritoNoEvento): ?>
    <section class="admin-card">
        <p>Você também é inscrito(a) neste evento. <a href="<?php echo url('eventoApp/index/' . (int) $estande['evento_id']); ?>">Abrir o aplicativo do evento</a>.</p>
    </section>
<?php endif; ?>
