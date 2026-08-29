<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if ($submissaoAnterior === null): ?>
    <p><em>Esta equipe não possui submissão registrada nessa etapa.</em></p>
<?php else: ?>
    <p class="ficha-submissao-enviada-em">
        Etapa: <?php echo htmlspecialchars($etapaComparacao['nome'], ENT_QUOTES, 'UTF-8'); ?>
        — enviada em <?php echo htmlspecialchars(formatarDataHora($submissaoAnterior['criado_em']), ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <?php if (empty($conteudoAnterior)): ?>
        <p><em>Esta submissão não tem conteúdo disponível para comparação.</em></p>
    <?php else: ?>
        <div class="ficha-submissao">
            <?php foreach ($conteudoAnterior as $item): ?>
                <?php $campo = $item['campo']; $valor = $item['valor']; ?>
                <div class="ficha-item">
                    <div class="ficha-item-label"><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="ficha-item-valor">
                        <?php if ($valor === null || $valor === ''): ?>
                            <em>Não preenchido</em>

                        <?php elseif ($campo['tipo'] === 'link_youtube'): ?>
                            <?php $videoId = \App\Validation\YoutubeValidador::extrairId($valor); ?>
                            <?php if ($videoId !== null): ?>
                                <div style="position:relative; max-width:480px; padding-top:270px;">
                                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'); ?>"
                                            style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                            allowfullscreen></iframe>
                                </div>
                            <?php elseif (linkHttpValido($valor)): ?>
                                <a href="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>

                        <?php elseif ($campo['tipo'] === 'link_externo'): ?>
                            <?php if (linkHttpValido($valor)): ?>
                                <a href="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>

                        <?php else: ?>
                            <?php echo nl2br(htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8')); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
