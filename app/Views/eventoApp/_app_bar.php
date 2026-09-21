<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 41: barra superior compacta ("app-bar") das telas do fluxo do Evento -
 * icone do aplicativo (nao o logo institucional - Configuracoes Gerais,
 * iconeAppUrl(), app/helpers.php) + titulo (nome do evento sendo visto, ou
 * o nome do aplicativo quando nao ha' evento) + sino de notificacoes + botao
 * de menu, tudo numa linha so' (o botao de ajuda contextual fica dentro do
 * cartao de conteudo, ver eventoApp/_ajuda_card.php). Substitui o
 * <header class="site-header"> do sistema web comum, que nao fazia parte
 * deste design (aparencia de aplicativo, nao de pagina web). $tituloTopo e
 * $eventoId sao definidos pela view antes de incluir este parcial.
 *
 * O menu (_menu_painel.php) e' incluido AQUI DENTRO, como filho deste
 * <header> - .app-menu-lateral usa position:absolute ancorado no ancestral
 * posicionado mais proximo (.app-bar e' position:relative); um "position:
 * absolute" so' enxerga ANCESTRAIS, nunca irmaos, entao incluir o menu como
 * irmao do header (como era antes) fazia ele se ancorar num elemento bem
 * mais distante da arvore, aparecendo fora da area visivel da tela (bug
 * real do teste de fumaca desta fase).
 */
?>
<header class="app-bar">
    <img src="<?php echo htmlspecialchars(iconeAppUrl('icon-192.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Ícone do aplicativo" class="app-bar-icone">
    <h1 class="app-bar-titulo"><?php echo htmlspecialchars($tituloTopo, ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php
    // Fase 44: variaveis calculadas aqui mesmo (nao em layout.php) porque
    // esta app-bar e' incluida DENTRO da view (View::renderizarConteudo()),
    // que roda ANTES de layout.php (View::renderizar()) - o sino do topbar
    // do painel administrativo calcula as suas dentro de layout.php porque
    // e' montado la' mesmo, fora do $conteudo da view; aqui nao da' pra
    // reaproveitar o mesmo calculo, so' chegaria pronto tarde demais.
    $notificacoesSinoClasseBotao = 'app-bar-botao';
    $repoNotificacoesAppBar = new \App\Repositories\NotificacaoPainelRepository();
    $notificacoesRecentes = \App\Core\Auth::autenticado() ? $repoNotificacoesAppBar->listarRecentes(\App\Core\Auth::usuarioId()) : [];
    $notificacoesNaoLidas = \App\Core\Auth::autenticado() ? $repoNotificacoesAppBar->contarNaoLidas(\App\Core\Auth::usuarioId()) : 0;
    ?>
    <?php require __DIR__ . '/../_notificacoes_sino.php'; ?>
    <button type="button" class="app-bar-botao" data-abrir-painel="painel-menu-app" aria-label="Menu" title="Menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="4" y1="6" x2="20" y2="6"></line>
            <line x1="4" y1="12" x2="20" y2="12"></line>
            <line x1="4" y1="18" x2="20" y2="18"></line>
        </svg>
    </button>
    <?php require __DIR__ . '/_menu_painel.php'; ?>
</header>
<?php
/**
 * Fase 48B: o flash sai da posicao antiga (acima do cabecalho, dentro de
 * layout.php) e passa a ficar aqui, logo abaixo do cabecalho, sobre a
 * camada de fundo em degrade. Fundo continua claro e as 3 cores fixas de
 * sucesso/alerta/erro sao mantidas (reconhecimento de cor, alem do icone);
 * a borda lateral usa a cor terciaria do tema como acento de identidade.
 */
if (!empty($_SESSION['flash'])):
    $flashMensagem = $_SESSION['flash'];
    $tipoFlash = classeFlash();
    unset($_SESSION['flash']);
    ?>
    <div class="app-flash <?php echo $tipoFlash; ?>">
        <?php if ($tipoFlash === 'sucesso'): ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        <?php elseif ($tipoFlash === 'erro'): ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        <?php else: ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        <?php endif; ?>
        <span><?php echo htmlspecialchars($flashMensagem, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/_banner_instalar.php'; ?>
