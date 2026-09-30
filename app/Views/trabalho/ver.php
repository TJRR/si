<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<?php /* O atributo avisa a rotina de rascunho (assets/js/rascunho-trabalho.js)
de que esta e' a tela em que o envio bem-sucedido cai: ela apaga o rascunho
desta aba, e so' quando o envio acabou de acontecer. */ ?>
<div class="site-page" data-rascunho-enviado-evento="<?php echo (int) $trabalho['evento_id']; ?>">
    <?php
    $eventoId = $trabalho['evento_id'];
    $tituloTopo = $trabalho['evento_nome'];
    $urlVoltar = url('trabalho/meusTrabalhos');
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
            <?php if (!empty($trabalho['consta_nos_anais'])): ?>
                <p>
                    <span class="selo-situacao verde">Publicado nos Anais</span>
                    <a href="<?php echo htmlspecialchars($trabalho['anais_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Abrir os Anais</a>
                </p>
            <?php endif; ?>
            <?php if ($trabalho['foi_desclassificado'] && !empty($trabalho['motivo_desclassificacao'])): ?>
                <p><strong>Motivo:</strong> <?php echo htmlspecialchars($trabalho['motivo_desclassificacao'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <p><strong>Protocolo:</strong> nº <?php echo (int) $trabalho['id']; ?></p>
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

        <?php if ($resultado !== null): ?>
            <div class="admin-card">
                <h3>Resultado da avaliação</h3>
                <p><strong>Situação:</strong> <span class="selo-situacao <?php echo $resultado['situacao'] === 'Aprovado' ? 'verde' : 'vermelho'; ?>"><?php echo htmlspecialchars($resultado['situacao'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                <?php if ($resultado['selecionado'] !== null): ?>
                    <p><strong>Seleção para apresentação:</strong> <?php echo $resultado['selecionado'] ? 'Selecionado' : 'Não selecionado'; ?></p>
                <?php endif; ?>
                <?php if ($resultado['nota'] !== null): ?>
                    <p><strong>Nota final:</strong> <?php echo htmlspecialchars($resultado['nota']['valor'], ENT_QUOTES, 'UTF-8'); ?><?php echo $resultado['nota']['maxima'] !== null ? ' de ' . htmlspecialchars($resultado['nota']['maxima'], ENT_QUOTES, 'UTF-8') : ''; ?></p>
                <?php endif; ?>
                <?php if ($resultado['posicao'] !== null): ?>
                    <p><strong>Posição:</strong> <?php echo (int) $resultado['posicao']['numero']; ?>º<?php echo $resultado['posicao']['total'] !== null ? ' entre ' . (int) $resultado['posicao']['total'] . ' trabalhos avaliados' : ''; ?></p>
                <?php endif; ?>
                <?php if (!empty($resultado['criterios'])): ?>
                    <p><strong>Média por critério:</strong></p>
                    <ul>
                        <?php foreach ($resultado['criterios'] as $criterio): ?>
                            <li><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $criterio['media'] !== null ? htmlspecialchars($criterio['media'], ENT_QUOTES, 'UTF-8') : 'sem nota'; ?> de <?php echo htmlspecialchars($criterio['maximo'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php elseif (!$trabalho['foi_desclassificado']): ?>
            <div class="admin-card">
                <h3>Resultado da avaliação</h3>
                <?php if ($resultadoPublicado): ?>
                    <p>Este trabalho não consta no resultado publicado. Em caso de dúvida, procure a organização do evento.</p>
                <?php else: ?>
                    <p>O resultado ainda não foi publicado. Você será avisado por e-mail e no aplicativo quando estiver disponível.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($versaoAnais)): ?>
            <?php /* Fase 54: envio da versao final para a montagem dos Anais;
            so' existe com o resultado publicado e o trabalho nos Anais. */ ?>
            <?php require __DIR__ . '/_versao_anais.php'; ?>
        <?php endif; ?>
    </div>
</div>
