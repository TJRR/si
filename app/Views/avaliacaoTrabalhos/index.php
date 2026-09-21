<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php $tituloTopo = 'Trabalhos para avaliar'; ?>
    <?php require __DIR__ . '/../eventoApp/_app_bar.php'; ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/../eventoApp/_ajuda_card.php'; ?>
        <h2>Trabalhos para avaliar</h2>

        <?php if (empty($designacoes)): ?>
            <p>Nenhum trabalho designado a você no momento.</p>
        <?php else: ?>
            <?php foreach ($designacoes as $item): ?>
                <div class="admin-card">
                    <p>
                        <strong>
                        <?php if ($item['sigilo_cego']): ?>
                            Trabalho nº <?php echo (int) $item['trabalho']['numero_sigilo']; ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars((string) $item['trabalho']['autor_principal_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                        </strong>
                    </p>
                    <p><?php echo htmlspecialchars($item['trabalho']['titulo'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><span class="selo-situacao <?php echo $item['completo'] ? 'verde' : 'laranja'; ?>"><?php echo $item['completo'] ? 'Avaliação concluída' : 'Avaliação pendente'; ?></span></p>
                    <p><a href="<?php echo url('avaliacaoTrabalhos/notar/' . (int) $item['trabalho']['id']); ?>" class="btn">Avaliar</a></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
