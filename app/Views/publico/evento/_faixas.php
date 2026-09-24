<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Reabertura da Fase 51: faixas da pagina do Evento com partial proprio (a
// home do Concurso continua usando home/_banners.php, intocado). Duas
// diferencas em relacao ao Concurso: a cor do texto respeita a cadastrada
// (antes era sempre branca, e a frase do tema sumia sobre fundo branco) e o
// formato "bandeirinha" desenha o primeiro paragrafo como a fita da
// identidade visual, com a ponta triangular, sobre fundo branco; os demais
// paragrafos viram a linha discreta abaixo dela.
?>
<?php if (!empty($banners)): ?>
    <?php foreach ($banners as $indiceFaixa => $faixa): ?>
        <?php
        $idFaixa = $indiceFaixa === 0 ? ' id="' . htmlspecialchars($ancoraSecao, ENT_QUOTES, 'UTF-8') . '"' : '';
        $formatoFaixa = isset($faixa['formato']) ? $faixa['formato'] : 'retangulo';
        $corTextoFaixa = isset($faixa['cor_texto']) ? $faixa['cor_texto'] : null;
        $destinoFaixa = null;

        if (!empty($faixa['cta_destino_valor'])) {
            $destinoFaixa = $faixa['cta_destino_tipo'] === 'ancora'
                ? '#' . ltrim($faixa['cta_destino_valor'], '#')
                : linkPublico($faixa['cta_destino_valor']);
        }
        ?>
        <?php if ($formatoFaixa === 'bandeirinha'): ?>
            <section class="evento-secao evento-faixa-bandeirinha"<?php echo $idFaixa; ?>>
                <div class="evento-conteudo evento-alinhar-<?php echo htmlspecialchars($faixa['conteudo_alinhamento'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="evento-fita-conteudo" style="<?php echo estiloDeCores(null, $corTextoFaixa, ['--cor-fita' => $faixa['cor_fundo']]); ?>"><?php echo $faixa['conteudo_html']; ?></div>
                    <?php if (!empty($faixa['cta_titulo']) && $destinoFaixa !== null): ?>
                        <div class="evento-botoes">
                            <a href="<?php echo htmlspecialchars($destinoFaixa, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-contorno"<?php echo $faixa['cta_destino_tipo'] === 'externo' ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo htmlspecialchars($faixa['cta_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <?php $imagemFaixa = !empty($faixa['imagem_desktop_path']) ? config('base_path') . '/assets/' . $faixa['imagem_desktop_path'] : null; ?>
            <section class="evento-secao evento-faixa-retangulo"<?php echo $idFaixa; ?> style="<?php echo estiloDeCores($imagemFaixa === null ? $faixa['cor_fundo'] : null, $corTextoFaixa); ?><?php echo $imagemFaixa !== null ? 'background-image:url(\'' . htmlspecialchars($imagemFaixa, ENT_QUOTES, 'UTF-8') . '\');' : ''; ?>">
                <?php if (!empty($faixa['imagem_alt'])): ?>
                    <span class="sr-only"><?php echo htmlspecialchars($faixa['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
                <div class="evento-conteudo evento-alinhar-<?php echo htmlspecialchars($faixa['conteudo_alinhamento'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="evento-faixa-texto"><?php echo $faixa['conteudo_html']; ?></div>
                    <?php if (!empty($faixa['cta_titulo']) && $destinoFaixa !== null): ?>
                        <div class="evento-botoes">
                            <a href="<?php echo htmlspecialchars($destinoFaixa, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-contorno"<?php echo $faixa['cta_destino_tipo'] === 'externo' ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo htmlspecialchars($faixa['cta_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
