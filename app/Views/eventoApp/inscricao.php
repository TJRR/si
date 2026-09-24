<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <?php
        // Fase 49B: documento/tipo de documento vêm de usuarios_perfil
        // (fonte única da pessoa), trazidos por JOIN em
        // EventoInscricaoRepository::buscarPorEventoEUsuario().
        $tipoDocumento = $inscricao['perfil_tipo_documento'];
        $rotuloDocumento = $tipoDocumento !== null ? $tipoDocumento : \App\Repositories\EventoCampoInscricaoRepository::ROTULO_NUMERO_DOCUMENTO;
        $documentoExibicao = $tipoDocumento === 'CPF'
            ? \App\Validation\CpfValidador::formatar((string) $inscricao['perfil_documento'])
            : (string) $inscricao['perfil_documento'];

        $qrSvg = \App\Services\QrCodeService::renderizarSvg($inscricao['codigo_credenciamento'], 220);
        $confirmada = $inscricao['homologado_em'] !== null;
        ?>

        <div class="admin-card cartao-credenciamento">
            <p class="cartao-credenciamento-nome"><?php echo htmlspecialchars($nomeParticipante, ENT_QUOTES, 'UTF-8'); ?></p>

            <span class="status-pill cartao-credenciamento-selo <?php echo $confirmada ? 'verde' : 'laranja'; ?>">
                <?php if ($confirmada): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php endif; ?>
                <?php echo $confirmada ? 'Inscrição confirmada' : 'Aguardando homologação'; ?>
            </span>

            <div class="cartao-credenciamento-qr">
                <span class="cartao-credenciamento-qr-wrap">
                    <?php echo $qrSvg; ?>
                    <button type="button" class="cartao-credenciamento-zoom" data-toggle-brilho="overlay-brilho-qr" aria-label="Ampliar código QR (Quick Response) para leitura" title="Ampliar código QR (Quick Response) para leitura">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="10" cy="10" r="7"></circle>
                            <line x1="10" y1="7" x2="10" y2="13"></line>
                            <line x1="7" y1="10" x2="13" y2="10"></line>
                            <line x1="21" y1="21" x2="15" y2="15"></line>
                        </svg>
                    </button>
                </span>
            </div>
            <p class="cartao-credenciamento-codigo"><?php echo htmlspecialchars($inscricao['codigo_credenciamento'], ENT_QUOTES, 'UTF-8'); ?></p>

            <ul class="cartao-credenciamento-dados">
                <li><strong><?php echo htmlspecialchars($rotuloDocumento, ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($documentoExibicao, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php foreach ($campos as $campo): ?>
                    <?php if ($campo['rotulo'] === \App\Repositories\EventoCampoInscricaoRepository::ROTULO_TIPO_DOCUMENTO) { continue; } ?>
                    <?php $valor = isset($respostas[$campo['id']]) ? $respostas[$campo['id']] : null; ?>
                    <?php if (!empty($valor)): ?>
                        <li><strong><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <p>
                <a href="<?php echo url('eventoApp/cracha/' . (int) $evento['id']); ?>" class="btn" target="_blank" rel="noopener">Imprimir crachá</a>
            </p>
        </div>
    </div>
</div>

<div id="overlay-brilho-qr" class="overlay-brilho-qr" hidden data-fechar-overlay-brilho>
    <?php echo $qrSvg; ?>
</div>
