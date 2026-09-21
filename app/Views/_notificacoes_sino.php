<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 44: sino de notificacoes extraido de layout.php (onde vivia inline,
 * so' dentro de $ehPainelInterno) para um parcial compartilhado - passa a
 * ser incluido tambem dentro da app-bar do app do evento
 * (eventoApp/_app_bar.php), que tem aparencia propria e nao ativa
 * $ehPainelInterno. $notificacoesRecentes/$notificacoesNaoLidas continuam
 * calculadas uma unica vez em layout.php. $notificacoesSinoClasseBotao
 * (opcional) troca a aparencia do botao pelo padrao de cada contexto - vazio
 * usa a classe "notificacoes-sino-botao" (estilo so' existe sob
 * body.admin-page); "app-bar-botao" reaproveita o circulo translucido ja'
 * pronto da app-bar do evento, que nao depende de body.admin-page.
 */
$notificacoesSinoClasseBotao = isset($notificacoesSinoClasseBotao) && $notificacoesSinoClasseBotao !== ''
    ? 'notificacoes-sino-botao ' . $notificacoesSinoClasseBotao
    : 'notificacoes-sino-botao';
?>
<div class="notificacoes-sino-wrapper">
    <button type="button" id="notificacoes-sino-botao" class="<?php echo htmlspecialchars($notificacoesSinoClasseBotao, ENT_QUOTES, 'UTF-8'); ?>" title="Notificações" aria-haspopup="true" aria-expanded="false" aria-controls="notificacoes-sino-painel">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        <?php if (!empty($notificacoesNaoLidas)): ?>
            <span class="notificacoes-sino-badge"><?php echo $notificacoesNaoLidas > 9 ? '9+' : $notificacoesNaoLidas; ?></span>
        <?php endif; ?>
    </button>
    <div id="notificacoes-sino-painel" class="notificacoes-sino-painel">
        <div class="notificacoes-sino-cabecalho">
            <span>Notificações</span>
            <?php if (!empty($notificacoesNaoLidas)): ?>
                <form method="post" action="<?php echo url('notificacoesPainel/marcarTodasLidas'); ?>"><?= campoCsrf() ?>
                    <button type="submit" class="btn-icone" title="Marcar todas como lidas">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="notificacoes-sino-lista">
            <?php if (empty($notificacoesRecentes)): ?>
                <p class="notificacoes-sino-vazio">Nenhuma notificação ainda.</p>
            <?php else: ?>
                <?php foreach ($notificacoesRecentes as $notificacao): ?>
                    <div class="notificacoes-sino-linha<?php echo empty($notificacao['lida']) ? ' nao-lida' : ''; ?>">
                        <a class="notificacoes-sino-item" href="<?php echo url('notificacoesPainel/abrir/' . (int) $notificacao['id']); ?>">
                            <span class="notificacoes-sino-titulo"><?php echo htmlspecialchars($notificacao['titulo'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="notificacoes-sino-mensagem">
                                <?php echo htmlspecialchars($notificacao['mensagem'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($notificacao['estado_participante'])): ?>
                                    <span class="status-pill <?php echo \App\Services\PermissaoParticipanteService::corDoEstado($notificacao['estado_participante']); ?>"><?php echo \App\Services\PermissaoParticipanteService::rotuloDoEstado($notificacao['estado_participante']); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                        <?php if (empty($notificacao['lida'])): ?>
                            <?php $ehConvitePendente = $notificacao['tipo'] === 'participante_email_completo'; ?>
                            <?php $ehCpfAlterado = $notificacao['tipo'] === 'cpf_alterado_pendente'; ?>
                            <?php
                                $tituloIcone = 'Marcar como lida';
                                if ($ehConvitePendente) {
                                    $tituloIcone = 'Convidar acesso agora';
                                } elseif ($ehCpfAlterado) {
                                    $tituloIcone = 'Homologar agora';
                                }
                            ?>
                            <form method="post" action="<?php echo url('notificacoesPainel/marcarLida/' . (int) $notificacao['id']); ?>"><?= campoCsrf() ?>
                                <button type="submit" class="btn-icone" title="<?php echo $tituloIcone; ?>">
                                    <?php if ($ehConvitePendente): ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M22 2 11 13"></path>
                                            <path d="M22 2 15 22l-4-9-9-4 20-7Z"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
