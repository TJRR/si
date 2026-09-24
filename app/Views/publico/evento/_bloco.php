<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Reabertura da Fase 51: bloco de conteudo da pagina do Evento com partial
// proprio (a home do Concurso continua usando home/_bloco_livre.php,
// intocado). Fundo de borda a borda, conteudo no conteiner largo da pagina,
// etiqueta colorida acima do titulo, cores proprias dos dois botoes e a
// opcao de herdar a cor do rodape e encostar nele (chamada final de
// inscricao). Os destinos dos botoes passam por linkPublico(): rota interna
// do sistema digitada sem o endereco completo nao vira endereco quebrado.
// isset(): colunas acrescentadas na reabertura; banco ainda nao migrado
// nao pode gerar aviso na pagina publica.
$blocoEvento += [
    'etiqueta' => null, 'etiqueta_cor' => null, 'usar_cor_rodape' => 0,
    'cta_cor_fundo' => null, 'cta_cor_texto' => null, 'cta2_cor_fundo' => null, 'cta2_cor_texto' => null,
];
$usarCorRodape = !empty($blocoEvento['usar_cor_rodape']);
$classesBloco = 'evento-secao evento-bloco evento-alinhar-botoes-' . htmlspecialchars($blocoEvento['cta_alinhamento'], ENT_QUOTES, 'UTF-8');
$classesBloco .= $usarCorRodape ? ' evento-bloco-rodape' : '';
$classesBloco .= corEhClara($usarCorRodape ? '#191919' : $blocoEvento['cor_fundo']) ? ' evento-fundo-claro' : ' evento-fundo-forte';
$classesBloco .= $blocoEvento['cta_alinhamento'] === 'centro' ? ' evento-bloco-centralizado' : '';
$linkBotao1 = linkPublico($blocoEvento['cta_link']);
$linkBotao2 = linkPublico($blocoEvento['cta2_link']);
?>
<section class="<?php echo $classesBloco; ?>" id="<?php echo htmlspecialchars($ancoraSecao, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo estiloDeCores($usarCorRodape ? null : $blocoEvento['cor_fundo'], $blocoEvento['cor_texto']); ?>">
    <div class="evento-conteudo">
        <div class="evento-bloco-corpo evento-bloco-imagem-<?php echo htmlspecialchars($blocoEvento['imagem_posicao'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (!empty($blocoEvento['imagem_path'])): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $blocoEvento['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $blocoEvento['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="evento-bloco-imagem">
            <?php endif; ?>
            <div class="evento-bloco-texto">
                <div class="evento-secao-cabecalho">
                    <?php if (!empty($blocoEvento['etiqueta'])): ?>
                        <p class="evento-secao-etiqueta" style="<?php echo estiloDeCores(null, $blocoEvento['etiqueta_cor']); ?>"><?php echo htmlspecialchars($blocoEvento['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <h2 class="evento-secao-titulo"><?php echo htmlspecialchars($blocoEvento['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (!empty($blocoEvento['conteudo_html'])): ?>
                        <div class="evento-secao-texto"><?php echo $blocoEvento['conteudo_html']; ?></div>
                    <?php endif; ?>
                </div>
                <?php $temBotao1 = !empty($blocoEvento['cta_titulo']) && $linkBotao1 !== ''; ?>
                <?php $temBotao2 = !empty($blocoEvento['cta2_titulo']) && $linkBotao2 !== ''; ?>
                <?php if ($temBotao1 || $temBotao2): ?>
                    <div class="evento-botoes">
                        <?php if ($temBotao1): ?>
                            <a href="<?php echo htmlspecialchars($linkBotao1, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-destaque" style="<?php echo estiloDeCores($blocoEvento['cta_cor_fundo'], $blocoEvento['cta_cor_texto']); ?>"><?php echo htmlspecialchars($blocoEvento['cta_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; ?>
                        <?php if ($temBotao2): ?>
                            <a href="<?php echo htmlspecialchars($linkBotao2, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-contorno" style="<?php echo estiloDeCores($blocoEvento['cta2_cor_fundo'], $blocoEvento['cta2_cor_texto']); ?>"><?php echo htmlspecialchars($blocoEvento['cta2_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
