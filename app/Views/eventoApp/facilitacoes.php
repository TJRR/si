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
        <h2>Minhas facilitações</h2>
        <p>Atividades em que você está designado como facilitador. Informe o código de presença pela internet abaixo assim que a sala virtual abrir, antes do início, para quem participa à distância confirmar presença e pontuar como quem está na sala, inclusive com o extra de pontualidade.</p>

        <?php if (!empty($pesquisaAberta)): ?>
            <?php /* Fase 57: quem conduziu atividade responde a pesquisa de
            satisfação, mesmo sem inscrição no evento. Sem inscrição não há
            pontos, e a própria tela da pesquisa avisa isso. */ ?>
            <div class="admin-card">
                <?php if (empty($pesquisaRespondida)): ?>
                    <p><strong>A pesquisa de satisfação está aberta.</strong></p>
                    <p>
                        <a href="<?php echo url('eventoApp/pesquisa/' . (int) $evento['id']); ?>" class="btn">Responder à pesquisa</a>
                    </p>
                <?php else: ?>
                    <p>Obrigado por responder à pesquisa de satisfação.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($certificadosAbertos)): ?>
            <?php /* Fase 59: quem conduziu atividade tem direito ao
            certificado do evento e ao de cada atividade que conduziu, e sem
            inscrição não passa pelo painel: este é o caminho dele. */ ?>
            <div class="admin-card">
                <p><strong>Os certificados deste evento estão disponíveis.</strong></p>
                <p>
                    <a href="<?php echo url('eventoApp/certificados/' . (int) $evento['id']); ?>" class="btn">Meus certificados</a>
                </p>
            </div>
        <?php endif; ?>

        <?php foreach ($facilitacoes as $facilitacao): ?>
        <div class="admin-card">
            <p><strong><?php echo htmlspecialchars($facilitacao['atividade_nome'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p>
                <?php echo htmlspecialchars(formatarDataHora($facilitacao['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars(formatarDataHora($facilitacao['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($facilitacao['local'])): ?>
                    , em <?php echo htmlspecialchars($facilitacao['local'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </p>

            <?php if ($facilitacao['modalidade'] === 'presencial'): ?>
                <p>Atividade presencial: os participantes confirmam presença lendo o código afixado na sala.</p>
            <?php elseif (!empty($facilitacao['codigo_presenca_online'])): ?>
                <p>Código de presença pela internet: <strong style="font-family:'Courier New',Courier,monospace;font-size:1.4em;letter-spacing:2px;"><?php echo htmlspecialchars($facilitacao['codigo_presenca_online'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <p style="color:#555;font-size:0.9em;">Informe este código assim que abrir a sala virtual, antes do início da atividade, a quem participa à distância.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($competicoes)): ?>
            <?php /* Fase 58: competições ligadas às atividades que a pessoa
            facilita. O código de participação fica "na mão do responsável"
            (dinâmica de pontos v2): quem canta ou compete lê este código na
            hora da participação. Uma camada de ampliação por competição,
            cada uma com identificador próprio na página. */ ?>
            <h3>Competições que você conduz</h3>
            <?php foreach ($competicoes as $competicao): ?>
                <?php
                $qrCompeticao = \App\Services\QrCodeService::renderizarSvg($competicao['codigo_participacao'], 220);
                $idCamada = 'camada-competicao-' . (int) $competicao['id'];
                ?>
                <div class="admin-card cartao-credenciamento">
                    <p><strong><?php echo htmlspecialchars($competicao['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                    <p><small>Mostre este código a quem participar, no momento da participação. Você conduz esta competição, então ela não pontua para você.</small></p>
                    <?php if (!empty($gincanaEncerrada)): ?>
                        <p class="status-pill laranja">A gincana foi encerrada: a participação não pontua mais.</p>
                    <?php endif; ?>
                    <div class="cartao-credenciamento-qr">
                        <span class="cartao-credenciamento-qr-wrap">
                            <?php echo $qrCompeticao; ?>
                            <button type="button" class="cartao-credenciamento-zoom" data-toggle-brilho="<?php echo htmlspecialchars($idCamada, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Ampliar código QR (Quick Response) para leitura" title="Ampliar código QR (Quick Response) para leitura">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="10" cy="10" r="7"></circle>
                                    <line x1="10" y1="7" x2="10" y2="13"></line>
                                    <line x1="7" y1="10" x2="13" y2="10"></line>
                                    <line x1="21" y1="21" x2="15" y2="15"></line>
                                </svg>
                            </button>
                        </span>
                    </div>
                    <p class="cartao-credenciamento-codigo"><?php echo htmlspecialchars($competicao['codigo_participacao'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div id="<?php echo htmlspecialchars($idCamada, ENT_QUOTES, 'UTF-8'); ?>" class="overlay-brilho-qr" hidden data-fechar-overlay-brilho="">
                    <?php echo $qrCompeticao; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
