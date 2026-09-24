<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51: cartoes que abrem o texto completo ao clique. Usa <details>
// nativo: teclado, leitor de tela e "abrir tudo" do navegador funcionam sem
// nenhum script, e os efeitos ficam por conta do CSS. Na reabertura, cada
// cartao ganhou fundo proprio (tom pastel) e a etiqueta na cor de destaque
// do item, e o texto do cabecalho da secao passou a fazer as vezes do
// bloco "Sobre", na mesma secao, como na identidade visual aprovada.
$classesCartoes = 'evento-cartoes'
    . ' evento-colunas-' . (int) $dadosSecao['colunas']
    . ' evento-efeito-hover-' . htmlspecialchars($dadosSecao['efeito_hover'], ENT_QUOTES, 'UTF-8')
    . ' evento-efeito-abrir-' . htmlspecialchars($dadosSecao['efeito_abrir'], ENT_QUOTES, 'UTF-8')
    . ' evento-efeito-fechar-' . htmlspecialchars($dadosSecao['efeito_fechar'], ENT_QUOTES, 'UTF-8');
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($itensSecao)): ?>
            <div class="<?php echo $classesCartoes; ?>">
                <?php foreach ($itensSecao as $item): ?>
                    <details class="evento-cartao" style="<?php echo estiloDeCores(isset($item['cor_fundo']) ? $item['cor_fundo'] : null, null, ['--cor-cartao' => $item['cor']]); ?>">
                        <summary>
                            <?php if (!empty($item['etiqueta'])): ?>
                                <span class="evento-cartao-etiqueta"><?php echo htmlspecialchars($item['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <strong class="evento-cartao-titulo"><?php echo htmlspecialchars((string) $item['titulo_exibicao'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($item['resumo'])): ?>
                                <span class="evento-cartao-resumo"><?php echo htmlspecialchars($item['resumo'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if (trim(strip_tags((string) $item['detalhe_exibicao'])) !== ''): ?>
                                <span class="evento-cartao-abrir" aria-hidden="true">Saiba mais</span>
                            <?php endif; ?>
                        </summary>
                        <div class="evento-cartao-detalhe"><?php echo $item['detalhe_exibicao']; ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
