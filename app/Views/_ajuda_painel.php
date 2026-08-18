<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 31: mapa de icones "feather" ja usados no restante do sistema
 * (btn-icone), copiados literalmente das telas reais (nao reinventados) -
 * so' pra dar identidade visual a cada operacao que corresponde a um
 * botao/icone de verdade na tela (ex.: "Remover" aqui usa o mesmo path da
 * lixeira que aparece em admin/concursos/index.php). Operacoes sem
 * correspondente exato (campos de formulario, abas, etc.) simplesmente nao
 * declaram 'icone' e aparecem sem SVG.
 */
$svgAjudaIcones = [
    'editar' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>',
    'remover' => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>',
    'ver' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>',
    'publicar' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
    'despublicar' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>',
    'arquivar' => '<polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line>',
    'mover_cima' => '<line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline>',
    'mover_baixo' => '<line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline>',
    'historico' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
    'convidar' => '<path d="M2 6h20v12H2z"></path><path d="M22 6l-10 7L2 6"></path>',
    'responder' => '<polyline points="9 17 4 12 9 7"></polyline><path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>',
    'escalar' => '<polyline points="15 17 20 12 15 7"></polyline><path d="M4 18v-2a4 4 0 0 1 4-4h12"></path>',
    'cadeado_fechado' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
    'cadeado_aberto' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path>',
    'rejeitar' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
    'baixar' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>',
];
?>
<div class="ajuda-painel">
    <?php if (!empty($ajuda['resumo'])): ?>
        <p class="ajuda-resumo"><?php echo htmlspecialchars($ajuda['resumo'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if (!empty($ajuda['operacoes'])): ?>
        <h3>O que você pode fazer aqui</h3>
        <dl class="ajuda-operacoes">
            <?php foreach ($ajuda['operacoes'] as $operacao): ?>
                <dt>
                    <?php if (!empty($operacao['icone']) && isset($svgAjudaIcones[$operacao['icone']])): ?>
                        <svg class="ajuda-operacao-icone" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $svgAjudaIcones[$operacao['icone']]; ?></svg>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($operacao['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </dt>
                <dd>
                    <?php echo nl2br(htmlspecialchars($operacao['como'], ENT_QUOTES, 'UTF-8')); ?>
                    <?php if (!empty($operacao['observacao'])): ?>
                        <br><em><?php echo nl2br(htmlspecialchars($operacao['observacao'], ENT_QUOTES, 'UTF-8')); ?></em>
                    <?php endif; ?>
                    <?php if (!empty($operacao['pills'])): ?>
                        <span class="ajuda-pills">
                            <?php foreach ($operacao['pills'] as $pill): ?>
                                <span class="status-pill <?php echo htmlspecialchars($pill['cor'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pill['rotulo'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </dd>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>

    <?php if (!empty($ajuda['conceitos'])): ?>
        <?php foreach ($ajuda['conceitos'] as $conceito): ?>
            <?php if (empty($conceito['texto'])) { continue; } ?>
            <div class="ajuda-conceito">
                <span class="ajuda-conceito-rotulo">Conceito do sistema</span>
                <h4><?php echo htmlspecialchars($conceito['titulo'], ENT_QUOTES, 'UTF-8'); ?></h4>
                <p><?php echo nl2br(htmlspecialchars($conceito['texto'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
