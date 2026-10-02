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
        <h2>Conectar com participante</h2>

        <?php if ((int) $config['ativo'] !== 1): ?>
            <?php /* Fase 55: com o modulo desligado no evento, nada de
            leitor na tela - ninguem le um codigo achando que vai pontuar. */ ?>
            <div class="admin-card">
                <p>As conexões não estão ativadas neste evento.</p>
            </div>
        <?php elseif (empty($dentroDaJanela)): ?>
            <div class="admin-card">
                <p>
                    As conexões só valem durante o evento, de
                    <?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a
                    <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>.
                </p>
            </div>
        <?php else: ?>
            <?php if (!empty($gincanaEncerrada)): ?>
                <p class="status-pill laranja">A gincana deste evento foi encerrada: as conexões continuam sendo registradas, mas não pontuam mais.</p>
            <?php endif; ?>
            <?php
            $leitorEndpoint = url('eventoApp/validarCodigo/' . (int) $evento['id']);
            $leitorTitulo = 'Ler o código de outra pessoa';
            $leitorInstrucao = empty($gincanaEncerrada)
                ? 'Aponte a câmera para o código do participante, na tela do aplicativo dele ou no crachá, ou digite-o abaixo. Uma leitura só já conecta e pontua as duas pessoas.'
                : 'Aponte a câmera para o código do participante, na tela do aplicativo dele ou no crachá, ou digite-o abaixo.';
            $leitorRotuloCampo = 'Código de 6 caracteres do participante';
            require __DIR__ . '/_leitor_codigo.php';
            ?>

            <?php /* Fase 58 (dinâmica de pontos v2: "QR fornecido diretamente
            na tela do aplicativo"): o próprio código fica nesta tela, para
            quem vai ser lido não precisar sair dela. Mesmo desenho e mesma
            camada de ampliação da tela "Minha inscrição". */ ?>
            <?php if (!empty($inscricao['codigo_credenciamento'])): ?>
                <?php $qrProprio = \App\Services\QrCodeService::renderizarSvg($inscricao['codigo_credenciamento'], 220); ?>
                <div class="admin-card cartao-credenciamento">
                    <p><strong>O seu código</strong></p>
                    <p><small>Mostre esta tela para a outra pessoa ler, se preferir que ela leia o seu código.</small></p>
                    <div class="cartao-credenciamento-qr">
                        <span class="cartao-credenciamento-qr-wrap">
                            <?php echo $qrProprio; ?>
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
                </div>
                <div id="overlay-brilho-qr" class="overlay-brilho-qr" hidden data-fechar-overlay-brilho="">
                    <?php echo $qrProprio; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="admin-card">
            <p>
                <strong>Seus pontos em conexões: <?php echo (int) $resumo['total_pontos']; ?></strong>
                <br>
                <?php echo (int) $resumo['total_conexoes']; ?>
                <?php echo (int) $resumo['total_conexoes'] === 1 ? 'pessoa conectada' : 'pessoas conectadas'; ?>
            </p>
        </div>

        <p><a href="<?php echo url('eventoApp/conexoes/' . (int) $evento['id']); ?>">Ver minhas conexões</a></p>
    </div>
</div>
