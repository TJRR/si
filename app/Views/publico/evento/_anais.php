<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $volumeAnais = isset($dadosSecao['volume']) ? $dadosSecao['volume'] : null; ?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if ($volumeAnais !== null): ?>
            <div class="evento-anais-volume">
                <p>
                    <strong><?php echo htmlspecialchars((string) $volumeAnais['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ($volumeAnais['rotulo_identificador'] !== ''): ?>
                        <br><small><?php echo htmlspecialchars($volumeAnais['rotulo_identificador'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </p>
                <p><a href="<?php echo htmlspecialchars($volumeAnais['url'], ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-escuro" target="_blank" rel="noopener">Baixar os Anais</a></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($itensSecao)): ?>
            <div class="evento-anais-selecionados">
                <h3>Trabalhos selecionados</h3>
                <?php foreach ($itensSecao as $eixoSelecionados => $trabalhosDoEixo): ?>
                    <?php if ($eixoSelecionados !== ''): ?>
                        <h4><?php echo htmlspecialchars($eixoSelecionados, ENT_QUOTES, 'UTF-8'); ?></h4>
                    <?php endif; ?>
                    <ul>
                        <?php foreach ($trabalhosDoEixo as $trabalhoSelecionado): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($trabalhoSelecionado['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($trabalhoSelecionado['autores'])): ?>
                                    <br><small><?php echo htmlspecialchars(implode(', ', $trabalhoSelecionado['autores']), ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
