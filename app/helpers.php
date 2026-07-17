<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

function config($chave)
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }

    return isset($config[$chave]) ? $config[$chave] : null;
}

function url($rota)
{
    return config('base_path') . '/index.php?r=' . $rota;
}

function urlAbsoluta($rota)
{
    $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

    return $esquema . '://' . $host . url($rota);
}

/**
 * Indica se a requisicao atual veio do JS de navegacao da arvore (fetch com o
 * cabecalho X-Requisicao: parcial), pedindo so o fragmento de conteudo em vez
 * da pagina completa com layout.
 */
function requisicaoParcial()
{
    return isset($_SERVER['HTTP_X_REQUISICAO']) && $_SERVER['HTTP_X_REQUISICAO'] === 'parcial';
}

/**
 * Formata uma data vinda do banco (Y-m-d ou null) para o padrao brasileiro
 * (d/m/Y) usado em toda exibicao de data do sistema - o MySQL sempre devolve
 * ISO (Y-m-d), que nao deve ir pra tela sem passar por aqui. Retorna string
 * vazia para null; quem chama decide o texto de fallback (ex.: "Período não
 * definido").
 */
function formatarData($data)
{
    return $data !== null ? date('d/m/Y', strtotime($data)) : '';
}

/**
 * Logo GLOBAL/default do sistema (usado no topbar do painel, paginas
 * convidadas, e como fallback da home publica quando a edicao ativa nao tem
 * logo proprio). Fase 18: fonte de verdade passou de conteudos_site
 * (chave 'logo_site', tela "Páginas") para configuracoes_visuais.logo_path
 * (tela "Tema") - mantem o fallback antigo por compatibilidade com o logo
 * ja enviado em producao antes desta fase, ate o admin reenviar pela tela
 * nova.
 */
function logoAtual()
{
    $configVisual = (new \App\Repositories\ConfiguracaoVisualRepository())->buscar();

    if ($configVisual !== false && !empty($configVisual['logo_path'])) {
        return config('base_path') . '/assets/' . $configVisual['logo_path'];
    }

    $logoConteudo = (new \App\Repositories\ConteudoSiteRepository())->buscarPorChave('logo_site');

    return $logoConteudo !== null && !empty($logoConteudo['arquivo_path'])
        ? config('base_path') . '/assets/' . $logoConteudo['arquivo_path']
        : config('base_path') . '/assets/img/logo-padrao.png';
}

/**
 * Categoria semantica de uma acao de auditoria, para colorir a badge na
 * tela Auditoria (reaproveita as cores de .status-pill: verde/laranja/
 * vermelho) - por palavra-chave em vez de mapa explicito de cada uma das
 * dezenas de acoes distintas, ja que novas acoes vao continuar aparecendo
 * conforme o sistema cresce.
 */
function categoriaAcaoAuditoria($acao)
{
    $vermelho = ['remover', 'rejeitar', 'desvincular', 'excluir', 'deletar'];
    $laranja = ['logout', 'voltar_para_pendente', 'reabrir', 'falhou'];

    foreach ($vermelho as $termo) {
        if (strpos($acao, $termo) !== false) {
            return 'vermelho';
        }
    }

    foreach ($laranja as $termo) {
        if (strpos($acao, $termo) !== false) {
            return 'laranja';
        }
    }

    return 'verde';
}
