<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $trabalho['evento_id'];
    $tituloTopo = $trabalho['evento_nome'];
    require __DIR__ . '/../eventoApp/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/../eventoApp/_ajuda_card.php'; ?>
        <h2><?php echo htmlspecialchars($trabalho['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>

        <div class="admin-card">
            <?php
            $situacaoAtual = $trabalho['situacao_rotulo'];
            $corSituacao = 'laranja';
            if ($situacaoAtual === 'Aprovado') {
                $corSituacao = 'verde';
            } elseif (in_array($situacaoAtual, ['Desclassificado', 'Reprovado'], true)) {
                $corSituacao = 'vermelho';
            }
            ?>
            <p><span class="selo-situacao <?php echo $corSituacao; ?>"><?php echo htmlspecialchars($situacaoAtual, ENT_QUOTES, 'UTF-8'); ?></span></p>
            <?php if ($trabalho['foi_desclassificado'] && !empty($trabalho['motivo_desclassificacao'])): ?>
                <p><strong>Motivo:</strong> <?php echo htmlspecialchars($trabalho['motivo_desclassificacao'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <p><strong>Eixo temático:</strong> <?php echo htmlspecialchars((string) $trabalho['eixo_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Natureza:</strong> <?php echo htmlspecialchars((string) $trabalho['natureza_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Submetido em:</strong> <?php echo htmlspecialchars($trabalho['submetido_em'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="admin-card">
            <h3>Autoria</h3>
            <ul>
                <?php foreach ($autores as $autor): ?>
                    <li>
                        <?php echo htmlspecialchars($autor['nome'], ENT_QUOTES, 'UTF-8'); ?><?php echo (int) $autor['eh_autor_principal'] === 1 ? ' (autor principal)' : ' (coautor)'; ?>
                        <?php if (!empty($autor['cargo']) || !empty($autor['orgao_origem'])): ?>
                            <br><small><?php echo htmlspecialchars(trim((string) $autor['cargo'] . (!empty($autor['cargo']) && !empty($autor['orgao_origem']) ? ' - ' : '') . (string) $autor['orgao_origem']), ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p>O resultado da avaliação, quando disponível, aparece aqui.</p>
    </div>
</div>
