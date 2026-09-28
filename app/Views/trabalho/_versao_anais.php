<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 54: quadro "Versao final para os Anais" na tela do trabalho
 * (incluido por trabalho/ver.php). $versaoAnais e' o retorno de
 * EventoAnaisPdfFinalService::blocoParaAutor(), nunca nulo aqui; o envio
 * vai para trabalho/enviarVersaoAnais, que confere tudo de novo.
 */
$escAnais = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};
$arquivoAnais = $versaoAnais['arquivo'];
$prazoAnais = $versaoAnais['prazo'] !== null
    ? formatarDataHora($versaoAnais['prazo']) . ' ' . sufixoFusoHorario()
    : null;
?>
<div class="admin-card" id="versao-final-anais">
    <h3>Versão final para os Anais</h3>

    <?php if ($prazoAnais !== null && $versaoAnais['prazo_encerrado']): ?>
        <p><span class="selo-situacao vermelho">Prazo encerrado</span> Prazo encerrado em <?php echo $escAnais($prazoAnais); ?>.</p>
    <?php elseif ($prazoAnais !== null): ?>
        <p><strong>Prazo:</strong> até <?php echo $escAnais($prazoAnais); ?>.</p>
    <?php endif; ?>

    <?php if ($versaoAnais['instrucoes_html'] !== ''): ?>
        <div><?php echo $versaoAnais['instrucoes_html']; ?></div>
    <?php endif; ?>

    <?php if ($arquivoAnais !== null): ?>
        <p>
            <span class="selo-situacao verde">Versão final enviada</span>
            <?php echo $escAnais($arquivoAnais['nome_original']); ?>,
            <?php echo (int) $arquivoAnais['paginas'] === 1 ? '1 página' : (int) $arquivoAnais['paginas'] . ' páginas'; ?>,
            enviada em <?php echo $escAnais(formatarDataHora($arquivoAnais['enviado_em'])); ?>.
        </p>
    <?php else: ?>
        <p><span class="selo-situacao laranja">Versão final não enviada</span></p>
    <?php endif; ?>

    <?php if ($versaoAnais['pode_enviar']): ?>
        <?php
        $aoEnviarAnais = $arquivoAnais !== null
            ? 'if (!confirm(' . json_encode('Trocar a versão final enviada? O arquivo anterior é substituído.') . ')) { return false; } this.querySelector(\'button\').disabled = true;'
            : 'this.querySelector(\'button\').disabled = true;';
        ?>
        <form method="post" action="<?php echo url('trabalho/enviarVersaoAnais/' . (int) $trabalho['id']); ?>" enctype="multipart/form-data" onsubmit="<?php echo $escAnais($aoEnviarAnais); ?>"><?= campoCsrf() ?>
            <label>Arquivo em PDF da versão final
                <input type="file" name="pdf_final" accept="application/pdf" required>
            </label>
            <p class="trabalho-legenda">Somente PDF, até <?php echo (int) $versaoAnais['limite_mb']; ?> MB. Não é preciso numerar as páginas: o número de cada página é colocado no volume dos Anais. Se o sistema não conseguir ler o arquivo, salve-o de novo como PDF/A-1b e envie outra vez.</p>
            <button type="submit" class="btn"><?php echo $arquivoAnais !== null ? 'Trocar arquivo' : 'Enviar versão final'; ?></button>
        </form>
    <?php elseif (!$versaoAnais['eh_autor_principal']): ?>
        <p>Só o autor principal envia a versão final.</p>
    <?php elseif ($prazoAnais === null): ?>
        <p>O envio da versão final não está aberto no momento.</p>
    <?php endif; ?>
</div>
