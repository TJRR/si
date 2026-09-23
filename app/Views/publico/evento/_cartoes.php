<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51: cartoes que abrem o texto completo ao clique. Usa <details>
// nativo: teclado, leitor de tela e "abrir tudo" do navegador funcionam sem
// nenhum script, e os efeitos ficam por conta do CSS.
$classesCartoes = 'evento-cartoes'
    . ' evento-cartoes-colunas-' . (int) $dadosSecao['colunas']
    . ' evento-efeito-hover-' . htmlspecialchars($dadosSecao['efeito_hover'], ENT_QUOTES, 'UTF-8')
    . ' evento-efeito-abrir-' . htmlspecialchars($dadosSecao['efeito_abrir'], ENT_QUOTES, 'UTF-8')
    . ' evento-efeito-fechar-' . htmlspecialchars($dadosSecao['efeito_fechar'], ENT_QUOTES, 'UTF-8');
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($itensSecao)): ?>
            <div class="<?php echo $classesCartoes; ?>">
                <?php foreach ($itensSecao as $item): ?>
                    <details class="evento-cartao" style="<?php echo !empty($item['cor']) ? '--cor-cartao:' . htmlspecialchars($item['cor'], ENT_QUOTES, 'UTF-8') . ';' : ''; ?>">
                        <summary>
                            <?php if (!empty($item['etiqueta'])): ?>
                                <span class="evento-cartao-etiqueta"><?php echo htmlspecialchars($item['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <strong><?php echo htmlspecialchars((string) $item['titulo_exibicao'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($item['resumo'])): ?>
                                <span class="evento-cartao-resumo"><?php echo htmlspecialchars($item['resumo'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </summary>
                        <div class="evento-cartao-detalhe"><?php echo $item['detalhe_exibicao']; ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
