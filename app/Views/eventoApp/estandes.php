<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $categoriasEstande = \App\Repositories\EstandeRepository::CATEGORIAS; ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Estandes</h2>

        <p>
            <a href="<?php echo url('eventoApp/lerEstande/' . (int) $evento['id']); ?>" class="btn">Registrar visita</a>
        </p>

        <div class="admin-card">
            <p>
                <strong>Seus pontos em estandes: <?php echo (int) $resumo['total_pontos']; ?></strong>
                <br>
                <?php $quantidadeVisitas = count($resumo['visitas']); ?>
                <?php echo $quantidadeVisitas; ?> <?php echo $quantidadeVisitas === 1 ? 'estande visitado' : 'estandes visitados'; ?>
            </p>
            <?php if (!empty($resumo['visitas'])): ?>
                <ul>
                    <?php foreach ($resumo['visitas'] as $visita): ?>
                        <li>
                            <?php echo htmlspecialchars($visita['nome'], ENT_QUOTES, 'UTF-8'); ?>:
                            <?php echo (int) $visita['pontos_creditados']; ?> <?php echo (int) $visita['pontos_creditados'] === 1 ? 'ponto' : 'pontos'; ?>,
                            em <?php echo htmlspecialchars(formatarDataHora($visita['visitado_em']), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if (empty($estandes)): ?>
            <p>Nenhum estande disponível no momento.</p>
        <?php endif; ?>

        <?php foreach ($estandes as $estande): ?>
            <?php $visitado = isset($visitados[(int) $estande['id']]); ?>
            <div class="admin-card">
                <?php if (!empty($estande['logotipo_path'])): ?>
                    <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $estande['logotipo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $estande['logotipo_alt'], ENT_QUOTES, 'UTF-8'); ?>" style="display:block;max-width:100%;height:64px;object-fit:contain;margin:0 0 8px;" loading="lazy">
                <?php endif; ?>
                <p>
                    <strong><?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if (isset($categoriasEstande[$estande['categoria']])): ?>
                        <br><small><?php echo htmlspecialchars($categoriasEstande[$estande['categoria']], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </p>
                <?php if (!empty($estande['descricao_html'])): ?>
                    <div><?php echo sanitizarHtmlRico((string) $estande['descricao_html']); ?></div>
                <?php endif; ?>
                <p>
                    <?php if ($visitado): ?>
                        <span class="status-pill verde">Visitado: <?php echo (int) $visitados[(int) $estande['id']]['pontos_creditados']; ?> <?php echo (int) $visitados[(int) $estande['id']]['pontos_creditados'] === 1 ? 'ponto' : 'pontos'; ?></span>
                    <?php elseif (!empty($gincanaEncerrada)): ?>
                        <span class="status-pill">A gincana foi encerrada: a visita não pontua mais</span>
                    <?php else: ?>
                        <span class="status-pill"><?php echo (int) $estande['pontos_visita']; ?> <?php echo (int) $estande['pontos_visita'] === 1 ? 'ponto' : 'pontos'; ?> pela visita</span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
