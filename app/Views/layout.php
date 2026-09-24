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
$ehPaginaConvidado = isset($view) && in_array($view, ['auth/login', 'auth/cadastro', 'auth/definir_senha', 'auth/esqueci_senha', 'publico/evento_inscricao_cadastro'], true);

// Fase 41 (correcao pos-teste de fumaca): manifesto/service worker/aparencia
// de aplicativo (fundo colorido, app-bar, menu) - nunca em
// admin/avaliacao/concurso/home institucional. As telas do proprio app
// (painel/selecionar/inscricao) sempre ativam; a tela publica de inscricao
// (para quem ainda nao tem conta) so' ativa quando ehContextoApp() -
// celular (User-Agent) ou navegacao vinda do PWA ja' instalado, mesmo no
// computador (app/helpers.php). Defesa 1 das duas descritas no plano da
// Fase 41 (a segunda e' o fetch handler do proprio SW, que so' intercepta
// sub-recursos estaticos, nunca navegacao de pagina). $view aqui e' o
// CAMINHO DO ARQUIVO DE TEMPLATE (ex.: 'eventoApp/painel'), nao o nome da
// rota.
// Fase 49B (correcao apos o usuario reverter a leitura anterior): a
// submissao de Trabalhos pelo PROPRIO AUTOR passa a ser parte do
// aplicativo instalavel de verdade (manifesto/tema-color/service worker),
// com ponto de entrada dentro do painel do evento (ver eventoApp/painel.php)
// - decisao confirmada explicitamente pelo usuario, substitui o que ficou
// registrado antes no plano mestre/Fase 49 sobre isso ficar "fora do app".
// So' cobre as 4 telas de uso do AUTOR (trabalho/formulario, trabalho/ver,
// trabalho/meusTrabalhos, trabalho/indisponivel) - a avaliacao avulsa
// (avaliacaoTrabalhos/*, avaliador convidado) continua fora do app
// instalavel de proposito (decisao separada, confirmada tambem), so' com a
// mesma aparencia visual (ver $ehContextoEvento abaixo).
$ehAppEvento = isset($view) && (
    in_array($view, ['eventoApp/painel', 'eventoApp/selecionar', 'eventoApp/inscricao', 'eventoApp/ler', 'eventoApp/aviso', 'eventoApp/atividades', 'eventoApp/presenca', 'eventoApp/facilitacoes'], true)
    || ($view === 'publico/evento_inscricao' && ehContextoApp())
    || (isset($view) && strpos($view, 'trabalho/') === 0)
);
// Fase 49B: $ehContextoEvento cobre tudo que $ehAppEvento cobre, mais a
// avaliacao avulsa de Trabalhos (avaliacaoTrabalhos/*) - essa ganha so' a
// APARENCIA do app (fundo em degrade, app-bar, cartao de conteudo), nunca
// o manifesto/service worker, porque o avaliador avulso continua
// deliberadamente fora do aplicativo instalavel.
$ehContextoEvento = $ehAppEvento || (isset($view) && strpos($view, 'avaliacaoTrabalhos/') === 0);
// Fase 48B: cor deixou de ser configuracao unica (ConfiguracaoVisualRepository)
// e virou tema selecionavel por usuario (TemaVisualRepository) - vale em toda
// pagina, nao so' no app de Evento. $corVisual continua so' para o favicon,
// que nao faz parte de tema nenhum.
$corVisual = (new \App\Repositories\ConfiguracaoVisualRepository())->buscar();
$temaAtivo = (new \App\Repositories\TemaVisualRepository())->resolverAtivo(\App\Core\Auth::autenticado() ? \App\Core\Auth::usuarioId() : null);
$corPrimariaInicio = $temaAtivo['cor_primaria_inicio'];
$corPrimariaFim = $temaAtivo['cor_primaria_fim'];
$corSecundaria = $temaAtivo['cor_secundaria'];
$corTerciaria = $temaAtivo['cor_terciaria'];
$corDestaqueApp = $temaAtivo['cor_destaque_app'];
$corDestaqueAppTexto = corContrastante($corDestaqueApp);
$faviconSrc = $corVisual !== false && !empty($corVisual['favicon_path'])
    ? config('base_path') . '/assets/' . $corVisual['favicon_path']
    : config('base_path') . '/assets/img/favicon-padrao.png';

// Paginas "publico/*" montam o proprio <header> (nao usam o topbar abaixo) e
// recebem o logo diretamente via View::renderizarConteudo() - aqui so'
// precisa pro topbar do painel/paginas convidadas.
$ehPaginaPublicaComLogo = $ehPainelInterno || $ehPaginaConvidado;

if ($ehPaginaPublicaComLogo) {
    $logoAdminSrc = logoAtual($ehContextoEvento);
}

$modulosArvore = ['concursos', 'trilhas', 'etapas', 'temas', 'criterios', 'formulas', 'desempate', 'designacoes', 'vagasAvaliador', 'resultados', 'homologacao', 'formularios', 'campos', 'apuracao', 'categoriasAvaliador', 'premios', 'faqConcurso', 'documentos', 'eventosCronograma', 'mentoriaAdmin', 'oficinaAdmin', 'blocoConcurso', 'apresentacaoPitchAdmin'];

if ($ehPainelInterno && \App\Core\Auth::autenticado()) {
    $repoNotificacoes = new \App\Repositories\NotificacaoPainelRepository();
    $notificacoesRecentes = $repoNotificacoes->listarRecentes(\App\Core\Auth::usuarioId());
    $notificacoesNaoLidas = $repoNotificacoes->contarNaoLidas(\App\Core\Auth::usuarioId());

    // Fase 36 (Parte C.2): selo de estado na notificacao de CPF alterado -
    // participante_id vem dentro do JSON de `dados`, nao como coluna solta.
    $permissaoParticipante = new \App\Services\PermissaoParticipanteService();
    foreach ($notificacoesRecentes as &$notificacaoRecente) {
        if ($notificacaoRecente['tipo'] === 'cpf_alterado_pendente') {
            $dadosNotificacao = $notificacaoRecente['dados'] !== null ? json_decode($notificacaoRecente['dados'], true) : null;
            $notificacaoRecente['estado_participante'] = isset($dadosNotificacao['participante_id'])
                ? $permissaoParticipante->estadoDoParticipante((int) $dadosNotificacao['participante_id'])
                : null;
        }
    }
    unset($notificacaoRecente);
}

if ($ehPainelAdmin) {
    $rotaAtual = isset($_GET['r']) ? trim($_GET['r'], '/') : 'home/index';
    $partesRota = explode('/', $rotaAtual);
    $moduloAtual = $partesRota[0];
    $ehEscopoArvoreConcurso = in_array($moduloAtual, $modulosArvore, true);

    // Fase 39 (revisada): segunda arvore, independente da de Concurso -
    // "Eventos" e' aba de 1o nivel propria (ver NavegacaoService::
    // filhosDe('raizEvento', ...) e admin/_arvore.php, generalizado para
    // aceitar mais de uma raiz).
    $modulosArvoreEvento = ['eventos', 'eventoCabecalho', 'eventoSlides', 'eventoBanners', 'eventoBlocos', 'eventoSecoes', 'eventoDocumentos', 'atividadeTipos', 'eventoFormulario', 'atividades', 'trabalhos'];
    $ehEscopoArvoreEvento = in_array($moduloAtual, $modulosArvoreEvento, true);
    $ehEscopoArvore = $ehEscopoArvoreConcurso || $ehEscopoArvoreEvento;

    if ($ehEscopoArvoreEvento) {
        $arvoreRaizTipo = 'raizEvento';
        $arvoreRotulo = 'Navegação de eventos';
        $arvoreVazia = 'Nenhum evento cadastrado ainda.';
    }

    $abasAdmin = [];

    // Fase 29 (ajuste pos-push): "Painel" e "Concursos" ficavam
    // incondicionais aqui - qualquer perfil que caisse numa tela admin/*
    // via herdava as duas abas, mesmo sem acesso (ex.: Colaborador, que so'
    // pode abrir Duvidas). As duas passam a exigir administrador/suporte,
    // igual as demais abas deste bloco.
    if (\App\Core\Auth::possuiPerfil('administrador') || \App\Core\Auth::possuiPerfil('suporte')) {
        $abasAdmin[] = ['rotulo' => 'Painel', 'url' => 'home/administrativo', 'ativo' => $moduloAtual === 'home'];
    }

    // Fase 35: o banco GERAL de FAQ passa a aceitar Suporte, mas so' com
    // vinculo GLOBAL - temPerfil() sem concurso so' reconhece concurso_id
    // NULL, que e' o mesmo criterio do construtor de FaqAdminController.
    // possuiPerfil() aqui mostraria a aba pra quem esta escopado a um
    // concurso e so' levaria a um 403: esse perfil edita o FAQ da propria
    // edicao, pela arvore de Concursos ("FAQ desta edição").
    if (\App\Core\Auth::temPerfil('administrador') || \App\Core\Auth::temPerfil('suporte')) {
        $abasAdmin[] = ['rotulo' => 'FAQ', 'url' => 'faq/index', 'ativo' => $moduloAtual === 'faq'];
    }

    if (\App\Core\Auth::possuiPerfil('administrador')) {
        // Fase 18: "Páginas" (ConteudoAdminController, conteudos_site) sai do
        // menu - substituida pelas telas novas (Slides, Banners, Blocos de
        // conteudo, Contato). Rota/tabela preservadas (sem DROP), so' o
        // link de navegacao foi retirado.
        $abasAdmin[] = ['rotulo' => 'Auditoria', 'url' => 'auditoria/index', 'ativo' => $moduloAtual === 'auditoria'];
        // Fase 19 (#84 v2): Tema/Mídia/Slideshow/Banners/Blocos/Contato
        // deixaram de ser abas de nivel 1 - viraram sub-abas de
        // "Configurações" (ver NavegacaoService::$abasPorGrupo['configuracao']).
        $modulosConfiguracao = ['configuracoes', 'tema', 'midia', 'slides', 'banners', 'blocos', 'contatosConcurso', 'ordenacaoHome', 'seguranca'];
        $abasAdmin[] = ['rotulo' => 'Configurações', 'url' => 'configuracoes/index', 'ativo' => in_array($moduloAtual, $modulosConfiguracao, true)];
    }

    if (\App\Core\Auth::possuiPerfil('administrador') || \App\Core\Auth::possuiPerfil('suporte')) {
        $abasAdmin[] = ['rotulo' => 'Concursos', 'url' => 'concursos/index', 'ativo' => $ehEscopoArvoreConcurso];
        $abasAdmin[] = ['rotulo' => 'Eventos', 'url' => 'eventos/index', 'ativo' => $ehEscopoArvoreEvento];
    }

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
    <title><?php echo htmlspecialchars($titulo !== null ? $titulo : 'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do ' . nomeInstituicao(), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconSrc, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($ehAppEvento): ?>
    <link rel="manifest" href="<?php echo htmlspecialchars(url('eventoApp/manifesto'), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="<?php echo htmlspecialchars($corPrimariaInicio, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars(iconeAppUrl('icon-512.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <?php
    // Reabertura da Fase 51: fontes escolhidas para a pagina publica de um
    // evento (aba Cabecalho do evento, lista fechada). Endereco montado por
    // EventoConfiguracaoVisualRepository::urlFontes(), nunca digitado.
    ?>
    <?php if (!empty($dados['urlFontesEvento'])): ?>
    <link href="<?php echo htmlspecialchars($dados['urlFontesEvento'], ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo config('base_path'); ?>/assets/css/site.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/site.css'); ?>">
    <style>
        :root {
            --cor-primaria-inicio: <?php echo htmlspecialchars($corPrimariaInicio, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-primaria-fim: <?php echo htmlspecialchars($corPrimariaFim, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-secundaria: <?php echo htmlspecialchars($corSecundaria, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-terciaria: <?php echo htmlspecialchars($corTerciaria, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-destaque-app: <?php echo htmlspecialchars($corDestaqueApp, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-destaque-app-texto: <?php echo htmlspecialchars($corDestaqueAppTexto, ENT_QUOTES, 'UTF-8'); ?>;
        }
    </style>
    <?php if ($ehAppEvento): ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register(<?php echo json_encode(config('base_path') . '/service-worker.js', JSON_UNESCAPED_SLASHES); ?>);
            });
        }
    </script>
    <?php endif; ?>
</head>
<body class="<?php echo $ehPainelInterno ? 'admin-page' : ($ehPaginaConvidado ? 'guest-page' : ($ehContextoEvento ? 'app-evento-page' : '')); ?>">
<?php if ($ehPainelInterno && \App\Core\Auth::estaVisualizandoComoOutro()): ?>
    <div class="faixa-visualizacao-como">
        Visualizando como <strong><?php echo htmlspecialchars(\App\Core\Auth::nome(), ENT_QUOTES, 'UTF-8'); ?></strong> (somente leitura)
        <form method="post" action="<?php echo url('meuPerfil/pararVisualizacao'); ?>" style="display:inline;"><?= campoCsrf() ?>
            <button type="submit" class="faixa-visualizacao-como-botao">Voltar para minha conta</button>
        </form>
    </div>
<?php endif; ?>
<?php if ($ehPainelInterno): ?>
    <div class="admin-topbar">
      <div class="admin-largura-max">
        <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(($ehContextoEvento ? '' : 'Prêmio de Inovação ') . nomeInstituicao(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="admin-topbar-acoes">
            <?php require __DIR__ . '/_notificacoes_sino.php'; ?>
            <button type="button" id="ajuda-botao" class="topbar-icone" title="Ajuda desta tela" data-ajuda-titulo="<?php echo htmlspecialchars('Ajuda: ' . (string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModal(this.dataset.ajudaTitulo, document.getElementById('ajuda-painel-fonte').innerHTML)" <?php echo $ajudaHtml === null ? 'hidden' : ''; ?>>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </button>
            <div id="ajuda-painel-fonte" hidden><?php echo $ajudaHtml !== null ? $ajudaHtml : ''; ?></div>
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
    <?php if ($ehPainelAdmin && !empty($abasAdmin)): ?>
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
<?php if (!empty($ehEscopoArvore)): ?>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <?php require __DIR__ . '/admin/_arvore.php'; ?>
        </aside>
        <div class="admin-conteudo-wrapper">
            <?php if (!empty($abasSecundarias)): ?>
            <nav id="abas-admin" class="abas-secundarias" data-mecanismo-avaliacao-etapa="<?php echo htmlspecialchars((string) $mecanismoAvaliacaoEtapa, ENT_QUOTES, 'UTF-8'); ?>">
                <?php foreach ($abasSecundarias as $abaSecundaria): ?>
                    <a class="aba-secundaria<?php echo $abaSecundaria['ativa'] ? ' active' : ''; ?>" href="<?php echo url($abaSecundaria['url']); ?>" data-somente-avaliadores="<?php echo !empty($abaSecundaria['somenteAvaliadores']) ? '1' : '0'; ?>" <?php echo (!empty($abaSecundaria['somenteAvaliadores']) && $mecanismoAvaliacaoEtapa !== 'avaliadores') ? 'style="display:none;"' : ''; ?>>
                        <?php echo htmlspecialchars($abaSecundaria['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php else: ?>
            <nav id="abas-admin" class="abas-secundarias" style="display:none;" data-mecanismo-avaliacao-etapa="<?php echo htmlspecialchars((string) $mecanismoAvaliacaoEtapa, ENT_QUOTES, 'UTF-8'); ?>"></nav>
            <?php endif; ?>
            <main id="conteudo-admin">
                <?php if (!empty($_SESSION['flash'])): ?>
                    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>
                <?php echo $conteudo; ?>
            </main>
        </div>
    </div>
    <script src="<?php echo config('base_path'); ?>/assets/js/navegacao-arvore.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/navegacao-arvore.js'); ?>" defer></script>
<?php else: ?>
    <div class="admin-conteudo-flat">
        <?php if (!empty($abasSecundarias)): ?>
        <nav id="abas-admin" class="abas-secundarias">
            <?php foreach ($abasSecundarias as $abaSecundaria): ?>
                <a class="aba-secundaria<?php echo $abaSecundaria['ativa'] ? ' active' : ''; ?>" href="<?php echo url($abaSecundaria['url']); ?>">
                    <?php echo htmlspecialchars($abaSecundaria['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <?php /* Fase 48B: no app de Evento o flash sai daqui - _app_bar.php
        exibe logo abaixo do cabecalho, sobre a camada de fundo. Fase 49B:
        mesma regra vale para as telas de Trabalhos, que agora tambem
        montam sua propria _app_bar.php ($ehContextoEvento). */ ?>
        <?php if (!empty($_SESSION['flash']) && !$ehContextoEvento): ?>
            <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
        <?php echo $conteudo; ?>
    </div>
<?php endif; ?>
<?php if ($ehPainelInterno): ?>
    <script src="<?php echo config('base_path'); ?>/assets/js/notificacoes-sino.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notificacoes-sino.js'); ?>" defer></script>

    <!-- Fase 17 (Bug 5/Melhoria 1): shell do modal generico, injetado uma vez -->
    <div id="modal-generico" class="modal-overlay" hidden>
        <div class="modal-caixa" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
            <button type="button" class="modal-fechar" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            <h2 id="modal-titulo"></h2>
            <!-- Fase 33: barra de navegacao entre itens da mesma tabela.
                 Fica oculta quando o modal nao faz parte de uma sequencia. -->
            <div id="modal-navegacao" class="modal-navegacao" hidden>
                <button type="button" id="modal-anterior" class="btn-acao" onclick="navegarModal(-1);">&larr; Anterior</button>
                <span id="modal-posicao"></span>
                <button type="button" id="modal-proximo" class="btn-acao" onclick="navegarModal(1);">Próximo &rarr;</button>
            </div>
            <div id="modal-conteudo"></div>
        </div>
    </div>
    <script src="<?php echo config('base_path'); ?>/assets/js/modal.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/modal.js'); ?>" defer></script>
    <?php if ($ehPainelAdmin): ?>
    <script>window.SI_BASE_PATH = <?php echo json_encode(config('base_path')); ?>; window.SI_CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token']); ?>;</script>
    <script src="<?php echo config('base_path'); ?>/assets/js/editor-rico.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/editor-rico.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/reordenar-arrastar.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/reordenar-arrastar.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/campo-cor.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/campo-cor.js'); ?>" defer></script>
    <!-- Fase 48 (correcao pos-teste de fumaca): busca-usuario.js e
         atividade-tolerancia.js passam a carregar incondicionalmente (nao
         so' nas telas onde nasceram) porque os dois so' agem quando
         encontram seus elementos no DOM, e precisam reagir ao evento
         'conteudo-admin-atualizado' apos navegacao pela arvore lateral
         (ver assets/js/navegacao-arvore.js) - condicionar por $view nunca
         funcionaria pra isso, ja que essa navegacao nunca reexecuta os
         <script> de layout.php (achado real: os rotulos de tolerancia e a
         busca de facilitador so' funcionavam em F5 direto na URL). -->
    <script src="<?php echo config('base_path'); ?>/assets/js/busca-usuario.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/busca-usuario.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/atividade-tolerancia.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/atividade-tolerancia.js'); ?>" defer></script>
    <?php endif; ?>
<?php endif; ?>
<?php $ehPaginaPublicaSemTopbar = $ehPaginaConvidado || $ehContextoEvento || (isset($view) && strpos($view, 'publico/') === 0); ?>
<?php if ($ehPaginaPublicaSemTopbar && $ajudaHtml !== null): ?>
    <!-- Fase 31: paginas convidadas (login/cadastro/senha) e publico/* nao
         passam pelo bloco $ehPainelInterno acima (cada uma monta o proprio
         cabecalho, sem topbar), entao o shell do modal generico precisa ser
         injetado aqui tambem para a ajuda contextual funcionar nelas. Fase 41:
         mesmo motivo vale para as telas do aplicativo do Evento (app-bar
         propria, tambem sem topbar) - sem isso, abrirModal() nao existe e o
         botao de ajuda quebra com erro no console. -->
    <div id="ajuda-painel-fonte" hidden><?php echo $ajudaHtml; ?></div>
    <div id="modal-generico" class="modal-overlay" hidden>
        <div class="modal-caixa" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
            <button type="button" class="modal-fechar" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            <h2 id="modal-titulo"></h2>
            <!-- Fase 33: barra de navegacao entre itens da mesma tabela.
                 Fica oculta quando o modal nao faz parte de uma sequencia. -->
            <div id="modal-navegacao" class="modal-navegacao" hidden>
                <button type="button" id="modal-anterior" class="btn-acao" onclick="navegarModal(-1);">&larr; Anterior</button>
                <span id="modal-posicao"></span>
                <button type="button" id="modal-proximo" class="btn-acao" onclick="navegarModal(1);">Próximo &rarr;</button>
            </div>
            <div id="modal-conteudo"></div>
        </div>
    </div>
    <script src="<?php echo config('base_path'); ?>/assets/js/modal.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/modal.js'); ?>" defer></script>
<?php endif; ?>
<?php if (isset($view) && ($view === 'home/index' || strpos($view, 'publico/') === 0)): ?>
    <script src="<?php echo config('base_path'); ?>/assets/js/scrollspy.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/scrollspy.js'); ?>" defer></script>
    <?php if ($view === 'publico/edicoes/detalhe'): ?>
    <script src="<?php echo config('base_path'); ?>/assets/js/galeria-lightbox.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/galeria-lightbox.js'); ?>" defer></script>
    <?php endif; ?>
    <?php if ($view === 'home/index'): ?>
    <script src="<?php echo config('base_path'); ?>/assets/js/slideshow.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/slideshow.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/temas-desafios.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/temas-desafios.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/cabecalho-rolagem.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cabecalho-rolagem.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/cabecalho-flutuar.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cabecalho-flutuar.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/painel-lateral.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/painel-lateral.js'); ?>" defer></script>
    <?php endif; ?>
    <?php
    // Fase 50: pagina publica propria do Evento - mesmo carrossel/efeitos
    // de cabecalho da home do Concurso (slideshow.js/cabecalho-rolagem.js/
    // cabecalho-flutuar.js/painel-lateral.js), sem temas-desafios.js (nao
    // ha Temas/Desafios na pagina do Evento). Bloco aditivo e separado do
    // de 'home/index' acima - a comparacao de $view e exata, nao por
    // prefixo, entao sem isto a pagina nova so herdaria o scrollspy.js.
    ?>
    <?php if ($view === 'publico/evento_home'): ?>
    <script src="<?php echo config('base_path'); ?>/assets/js/slideshow.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/slideshow.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/cabecalho-rolagem.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cabecalho-rolagem.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/cabecalho-flutuar.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cabecalho-flutuar.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/painel-lateral.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/painel-lateral.js'); ?>" defer></script>
    <?php
    // Reabertura da Fase 51 (achado do teste de fumaca): contagem regressiva
    // e abas da programacao so' existem na pagina do Evento, mas estavam
    // incluidas no bloco da home do Concurso - na pagina do Evento o relogio
    // nao contava e as abas nao abriam. evento-pagina.js cuida da distancia
    // de rolagem das ancoras (altura do cabecalho fixo) e da entrada animada
    // dos cartoes.
    ?>
    <script src="<?php echo config('base_path'); ?>/assets/js/contagem-regressiva.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/contagem-regressiva.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/programacao-abas.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/programacao-abas.js'); ?>" defer></script>
    <script src="<?php echo config('base_path'); ?>/assets/js/evento-pagina.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/evento-pagina.js'); ?>" defer></script>
    <?php endif; ?>
    <?php
    // Fase 19 (#107): acesso direto ao suporte pelo WhatsApp. O numero vem de
    // Configuracoes > Contato (contatos_concurso.whatsapp, singleton global) -
    // nunca fixo no codigo. Sem numero cadastrado (ou com numero que nao da'
    // pra transformar em link, ver linkWhatsApp()), o botao nao aparece.
    $contatoSuporte = (new \App\Repositories\ContatoConcursoRepository())->buscar();
    $whatsappSuporte = $contatoSuporte !== null ? linkWhatsApp($contatoSuporte['whatsapp']) : null;
    ?>
    <?php if ($whatsappSuporte !== null): ?>
    <a href="<?php echo htmlspecialchars($whatsappSuporte, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="site-whatsapp-flutuante" aria-label="<?php echo htmlspecialchars('Falar com o suporte do ' . nomeUnidadeResponsavel() . ' pelo WhatsApp', ENT_QUOTES, 'UTF-8'); ?>">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.38a9.9 9.9 0 0 0 4.76 1.21h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.12-2.9-6.99A9.82 9.82 0 0 0 12.04 2Zm0 1.67c2.19 0 4.25.85 5.79 2.4a8.2 8.2 0 0 1 2.41 5.83c0 4.55-3.7 8.24-8.24 8.24a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.13.82.84-3.05-.2-.31a8.18 8.18 0 0 1-1.26-4.37c0-4.55 3.7-8.23 8.24-8.23h.04Z"></path></svg>
    </a>
    <?php endif; ?>
<?php endif; ?>
<?php if ($ehContextoEvento): ?>
    <!-- Fase 41: menu do aplicativo (botao hamburguer na app-bar + painel,
         ver app/Views/eventoApp/_app_bar.php/_menu_painel.php) reaproveita o
         mesmo componente generico de painel lateral da home publica. Fase
         49B: passa a valer tambem para trabalho/*/avaliacaoTrabalhos/*
         ($ehContextoEvento), que agora montam a mesma _app_bar.php. -->
    <script src="<?php echo config('base_path'); ?>/assets/js/painel-lateral.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/painel-lateral.js'); ?>" defer></script>
    <?php if ($ehAppEvento): ?>
    <!-- Fase 41 (correcao pos-teste de fumaca): item "Instalar aplicativo"
         sempre visivel no menu, em vez de depender do usuario encontrar a
         opcao escondida no menu de tres pontos do navegador. Fase 49B:
         continua restrito a $ehAppEvento (rotas eventoApp/*) de proposito -
         so' ali existe manifesto/service worker para o navegador considerar
         a pagina instalavel; nas telas de Trabalhos o banner ficaria inerte
         mesmo carregando o script (nunca aparece), entao nao ha motivo pra
         carregar. -->
    <script src="<?php echo config('base_path'); ?>/assets/js/instalar-app.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/instalar-app.js'); ?>" defer></script>
    <?php
    // Fase 50: mesmo botao de contato por WhatsApp da home publica (ver
    // comentario acima, ~linha 373) - existia so' ali, nunca tinha chegado
    // ao aplicativo instalavel do Evento (pendencia reportada pelo usuario
    // ainda na Fase 48B). Mesmo dado (contatos_concurso.whatsapp, singleton
    // global) e mesmo helper linkWhatsApp(); classe CSS propria
    // (.app-whatsapp-flutuante) porque aqui a cor fica fixa de proposito
    // (verde da marca WhatsApp, decisao do usuario), em vez de seguir a cor
    // dinamica do tema do evento como o botao de ajuda (.app-ajuda-card).
    $contatoSuporteApp = (new \App\Repositories\ContatoConcursoRepository())->buscar();
    $whatsappSuporteApp = $contatoSuporteApp !== null ? linkWhatsApp($contatoSuporteApp['whatsapp']) : null;
    ?>
    <?php if ($whatsappSuporteApp !== null): ?>
    <a href="<?php echo htmlspecialchars($whatsappSuporteApp, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="app-whatsapp-flutuante" aria-label="<?php echo htmlspecialchars('Falar com o suporte do ' . nomeUnidadeResponsavel() . ' pelo WhatsApp', ENT_QUOTES, 'UTF-8'); ?>">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.38a9.9 9.9 0 0 0 4.76 1.21h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.12-2.9-6.99A9.82 9.82 0 0 0 12.04 2Zm0 1.67c2.19 0 4.25.85 5.79 2.4a8.2 8.2 0 0 1 2.41 5.83c0 4.55-3.7 8.24-8.24 8.24a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.13.82.84-3.05-.2-.31a8.18 8.18 0 0 1-1.26-4.37c0-4.55 3.7-8.23 8.24-8.23h.04Z"></path></svg>
    </a>
    <?php endif; ?>
    <?php endif; ?>
    <!-- Fase 44: sino de notificacoes agora tambem na app-bar do evento
         (eventoApp/_app_bar.php) - mesmo script usado pelo painel interno. -->
    <script src="<?php echo config('base_path'); ?>/assets/js/notificacoes-sino.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notificacoes-sino.js'); ?>" defer></script>
    <?php if ($view === 'eventoApp/inscricao'): ?>
    <!-- Fase 42 (correcao pos-teste de fumaca): botao "Aumentar brilho para
         leitura" do cartao de credenciamento - so' existe em "Minha
         inscricao", nao precisa carregar nas demais telas do app. -->
    <script src="<?php echo config('base_path'); ?>/assets/js/brilho-cracha.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/brilho-cracha.js'); ?>" defer></script>
    <?php endif; ?>
    <?php if (in_array($view, ['eventoApp/ler', 'eventoApp/presenca'], true)): ?>
    <!-- Fase 43: componente de leitura de codigo (camera + digitacao
         manual) - existe em "Ler codigo" e, desde a Fase 47, em "Confirmar
         presenca" - reaproveitavel pelas Fases 49/50 (estandes, networking).
         window.SI_CSRF_TOKEN so' era definido dentro do bloco $ehPainelAdmin
         (linha ~358) - as
         telas do app do evento nunca tinham precisado de POST via fetch
         antes desta fase, entao a variavel nunca existia aqui (undefined),
         o header X-CSRF-Token ia como a string literal "undefined", e o
         Router rejeitava com 403 (bug real, achado no teste de fumaca). -->
    <script>window.SI_CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token']); ?>;</script>
    <script src="<?php echo config('base_path'); ?>/assets/js/leitor-codigo.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/leitor-codigo.js'); ?>" defer></script>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
