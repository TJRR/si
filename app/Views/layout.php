<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$ehPainelAdmin = isset($view) && (strpos($view, 'admin/') === 0 || $view === 'home/administrativo');
$prefixosPainelInterno = ['admin/', 'avaliacao/', 'participante/'];
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

$ehPaginaPublicaComLogo = $ehPainelInterno || $ehPaginaConvidado || (isset($view) && strpos($view, 'publico/') === 0);

if ($ehPaginaPublicaComLogo) {
    $logoConteudo = (new \App\Repositories\ConteudoSiteRepository())->buscarPorChave('logo_site');
    $logoAdminSrc = $logoConteudo !== null && !empty($logoConteudo['arquivo_path'])
        ? config('base_path') . '/assets/' . $logoConteudo['arquivo_path']
        : config('base_path') . '/assets/img/logo-padrao.png';
}

if ($ehPainelAdmin) {
    $rotaAtual = isset($_GET['r']) ? trim($_GET['r'], '/') : 'home/index';
    $partesRota = explode('/', $rotaAtual);
    $moduloAtual = $partesRota[0];

    $abasAdmin = [
        ['rotulo' => 'Painel', 'url' => 'home/administrativo', 'ativo' => $moduloAtual === 'home'],
        ['rotulo' => 'Páginas', 'url' => 'conteudo/index', 'ativo' => $moduloAtual === 'conteudo'],
        ['rotulo' => 'Tema', 'url' => 'tema/index', 'ativo' => $moduloAtual === 'tema'],
        ['rotulo' => 'Concursos', 'url' => 'concursos/index', 'ativo' => in_array($moduloAtual, ['concursos', 'trilhas', 'etapas', 'temas', 'criterios', 'formulas', 'desempate', 'designacoes', 'resultados', 'homologacao', 'formularios', 'campos'], true)],
        ['rotulo' => 'Usuários', 'url' => 'usuarios/index', 'ativo' => $moduloAtual === 'usuarios'],
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($titulo !== null ? $titulo : 'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR', ENT_QUOTES, 'UTF-8'); ?></title>
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
        <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR">
        <a href="<?php echo url('auth/logout'); ?>">Sair</a>
    </div>
    <?php if ($ehPainelAdmin): ?>
    <nav class="admin-tabs">
        <?php foreach ($abasAdmin as $aba): ?>
            <a class="admin-tab<?php echo $aba['ativo'] ? ' active' : ''; ?>" href="<?php echo url($aba['url']); ?>">
                <?php echo htmlspecialchars($aba['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if (!empty($breadcrumb)): ?>
    <nav class="admin-breadcrumb"><?php echo breadcrumb_html($breadcrumb); ?></nav>
    <?php endif; ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<?php echo $conteudo; ?>
</body>
</html>
