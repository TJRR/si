<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php $tituloTopo = 'Meus trabalhos'; ?>
    <?php $urlVoltar = url('eventoApp/index'); ?>
    <?php require __DIR__ . '/../eventoApp/_app_bar.php'; ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/../eventoApp/_ajuda_card.php'; ?>
        <h2>Meus trabalhos</h2>

        <?php if (empty($trabalhos)): ?>
            <p>Você ainda não submeteu nenhum trabalho.</p>
        <?php else: ?>
            <?php foreach ($trabalhos as $trabalho): ?>
                <?php
                $situacaoAtual = $trabalho['situacao_rotulo'];
                $corSituacao = 'laranja';
                if ($situacaoAtual === 'Aprovado') {
                    $corSituacao = 'verde';
                } elseif (in_array($situacaoAtual, ['Desclassificado', 'Reprovado'], true)) {
                    $corSituacao = 'vermelho';
                }
                ?>
                <div class="admin-card">
                    <p><strong><?php echo htmlspecialchars($trabalho['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong><?php echo isset($trabalho['sou_autor_principal']) && (int) $trabalho['sou_autor_principal'] === 0 ? ' (você é coautor)' : ''; ?></p>
                    <p>Protocolo nº <?php echo (int) $trabalho['id']; ?></p>
                    <p><?php echo htmlspecialchars($trabalho['evento_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><span class="selo-situacao <?php echo $corSituacao; ?>"><?php echo htmlspecialchars($situacaoAtual, ENT_QUOTES, 'UTF-8'); ?></span></p>
                    <p>Submetido em <?php echo htmlspecialchars($trabalho['submetido_em'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><a href="<?php echo url('trabalho/ver/' . (int) $trabalho['id']); ?>" class="btn">Ver detalhes</a></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
