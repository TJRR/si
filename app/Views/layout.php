<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$ehPainelAdmin = isset($view) && (strpos($view, 'admin/') === 0 || $view === 'home/administrativo');
$prefixosPainelInterno = ['admin/', 'avaliacao/', 'participante/', 'meuPerfil/'];
$ehPainelInterno = $ehPainelAdmin;
if (isset($view) && !$ehPainelInterno) {
    foreach ($prefixosPainelInterno as $prefixo) {
        if (strpos($view, $prefixo) === 0) {
            $ehPainelInterno = true;
            break;
        }
    }
}
$ehPaginaConvidado = isset($view) && in_array($view, ['auth/login', 'auth/cadastro', 'auth/definir_senha'], true);
$corVisual = (new \App\Repositories\ConfiguracaoVisualRepository())->buscar();
$corPrimariaInicio = $corVisual !== false ? $corVisual['cor_primaria_inicio'] : '#FF6600';
$corPrimariaFim = $corVisual !== false ? $corVisual['cor_primaria_fim'] : '#FF9955';
$corSecundaria = $corVisual !== false && !empty($corVisual['cor_secundaria']) ? $corVisual['cor_secundaria'] : '#191919';
$faviconSrc = $corVisual !== false && !empty($corVisual['favicon_path'])
    ? config('base_path') . '/assets/' . $corVisual['favicon_path']
    : config('base_path') . '/assets/img/favicon-padrao.png';

$ehPaginaPublicaComLogo = $ehPainelInterno || $ehPaginaConvidado || (isset($view) && strpos($view, 'publico/') === 0);

if ($ehPaginaPublicaComLogo) {
    $logoConteudo = (new \App\Repositories\ConteudoSiteRepository())->buscarPorChave('logo_site');
    $logoAdminSrc = $logoConteudo !== null && !empty($logoConteudo['arquivo_path'])
        ? config('base_path') . '/assets/' . $logoConteudo['arquivo_path']
        : config('base_path') . '/assets/img/logo-padrao.png';
}

$modulosArvore = ['concursos', 'trilhas', 'etapas', 'temas', 'criterios', 'formulas', 'desempate', 'designacoes', 'vagasAvaliador', 'resultados', 'homologacao', 'formularios', 'campos', 'apuracao', 'categoriasAvaliador'];

if ($ehPainelInterno && \App\Core\Auth::autenticado()) {
    $repoNotificacoes = new \App\Repositories\NotificacaoPainelRepository();
    $notificacoesRecentes = $repoNotificacoes->listarRecentes(\App\Core\Auth::usuarioId());
    $notificacoesNaoLidas = $repoNotificacoes->contarNaoLidas(\App\Core\Auth::usuarioId());
}

if ($ehPainelAdmin) {
    $rotaAtual = isset($_GET['r']) ? trim($_GET['r'], '/') : 'home/index';
    $partesRota = explode('/', $rotaAtual);
    $moduloAtual = $partesRota[0];
    $ehEscopoArvore = in_array($moduloAtual, $modulosArvore, true);

    $abasAdmin = [
        ['rotulo' => 'Painel', 'url' => 'home/administrativo', 'ativo' => $moduloAtual === 'home'],
    ];

    if (\App\Core\Auth::possuiPerfil('administrador')) {
        $abasAdmin[] = ['rotulo' => 'Páginas', 'url' => 'conteudo/index', 'ativo' => $moduloAtual === 'conteudo'];
        $abasAdmin[] = ['rotulo' => 'Tema', 'url' => 'tema/index', 'ativo' => $moduloAtual === 'tema'];
        $abasAdmin[] = ['rotulo' => 'Auditoria', 'url' => 'auditoria/index', 'ativo' => $moduloAtual === 'auditoria'];
        $abasAdmin[] = ['rotulo' => 'Configurações', 'url' => 'configuracoes/index', 'ativo' => $moduloAtual === 'configuracoes'];
    }

    $abasAdmin[] = ['rotulo' => 'Concursos', 'url' => 'concursos/index', 'ativo' => $ehEscopoArvore];

    if (\App\Core\Auth::possuiPerfil('administrador')) {
        $abasAdmin[] = ['rotulo' => 'Usuários', 'url' => 'usuarios/index', 'ativo' => $moduloAtual === 'usuarios'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($titulo !== null ? $titulo : 'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconSrc, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo config('base_path'); ?>/assets/css/site.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/site.css'); ?>">
    <style>
        :root {
            --cor-primaria-inicio: <?php echo htmlspecialchars($corPrimariaInicio, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-primaria-fim: <?php echo htmlspecialchars($corPrimariaFim, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-secundaria: <?php echo htmlspecialchars($corSecundaria, ENT_QUOTES, 'UTF-8'); ?>;
        }
    </style>
</head>
<body class="<?php echo $ehPainelInterno ? 'admin-page' : ($ehPaginaConvidado ? 'guest-page' : ''); ?>">
<?php if ($ehPainelInterno): ?>
    <div class="admin-topbar">
      <div class="admin-largura-max">
        <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR">
        <div class="admin-topbar-acoes">
            <div class="notificacoes-sino-wrapper">
                <button type="button" id="notificacoes-sino-botao" class="notificacoes-sino-botao" title="Notificações" aria-haspopup="true" aria-expanded="false" aria-controls="notificacoes-sino-painel">
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
                            <form method="post" action="<?php echo url('notificacoesPainel/marcarTodasLidas'); ?>">
                                <button type="submit" class="notificacoes-sino-marcar-todas">Marcar todas como lidas</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="notificacoes-sino-lista">
                        <?php if (empty($notificacoesRecentes)): ?>
                            <p class="notificacoes-sino-vazio">Nenhuma notificação ainda.</p>
                        <?php else: ?>
                            <?php foreach ($notificacoesRecentes as $notificacao): ?>
                                <a class="notificacoes-sino-item<?php echo empty($notificacao['lida']) ? ' nao-lida' : ''; ?>" href="<?php echo url('notificacoesPainel/abrir/' . (int) $notificacao['id']); ?>">
                                    <span class="notificacoes-sino-titulo"><?php echo htmlspecialchars($notificacao['titulo'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="notificacoes-sino-mensagem"><?php echo htmlspecialchars($notificacao['mensagem'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="<?php echo url('meuPerfil/index'); ?>" class="topbar-icone" title="Meu perfil">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <a href="<?php echo url('auth/logout'); ?>" class="topbar-icone" title="Sair">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
      </div>
    </div>
    <?php if ($ehPainelAdmin): ?>
    <nav class="admin-tabs">
      <div class="admin-largura-max">
        <?php foreach ($abasAdmin as $aba): ?>
            <a class="admin-tab<?php echo $aba['ativo'] ? ' active' : ''; ?>" href="<?php echo url($aba['url']); ?>">
                <?php echo htmlspecialchars($aba['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
      </div>
    </nav>
    <?php endif; ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<?php if (!empty($ehEscopoArvore)): ?>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <?php require __DIR__ . '/admin/_arvore.php'; ?>
        </aside>
        <div class="admin-conteudo-wrapper">
            <?php if (!empty($abasSecundarias)): ?>
            <nav id="abas-admin" class="abas-secundarias">
                <?php foreach ($abasSecundarias as $abaSecundaria): ?>
                    <a class="aba-secundaria<?php echo $abaSecundaria['ativa'] ? ' active' : ''; ?>" href="<?php echo url($abaSecundaria['url']); ?>">
                        <?php echo htmlspecialchars($abaSecundaria['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php else: ?>
            <nav id="abas-admin" class="abas-secundarias" style="display:none;"></nav>
            <?php endif; ?>
            <main id="conteudo-admin"><?php echo $conteudo; ?></main>
        </div>
    </div>
    <script>window.SI_BASE_PATH = <?php echo json_encode(config('base_path')); ?>;</script>
    <script src="<?php echo config('base_path'); ?>/assets/js/navegacao-arvore.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/navegacao-arvore.js'); ?>" defer></script>
<?php else: ?>
    <div class="admin-conteudo-flat"><?php echo $conteudo; ?></div>
<?php endif; ?>
<?php if ($ehPainelInterno): ?>
    <script src="<?php echo config('base_path'); ?>/assets/js/notificacoes-sino.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notificacoes-sino.js'); ?>" defer></script>
<?php endif; ?>
</body>
</html>
