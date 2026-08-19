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
 * Mesmo padrao de formatarData(), mas com hora - usado pra prazos DATETIME
 * (ex.: etapas.prazo_final_submissao, Fase 27) que precisam mostrar a
 * precisao de minuto, nao so o dia.
 */
function formatarDataHora($data)
{
    return $data !== null ? date('d/m/Y H:i', strtotime($data)) : '';
}

/**
 * Fase 29 (Tira-Duvidas): iniciais do avatar - primeira letra das duas
 * primeiras palavras do nome (ex.: "Victor Mouzinho Spinelli" -> "VM").
 * Usado nas telas de detalhe de duvida (participante e admin).
 */
function iniciaisAvatar($nome)
{
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';

    foreach (array_slice($partes, 0, 2) as $parte) {
        $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
    }

    return $iniciais;
}

/**
 * Fase 29 (Melhoria 5): "(GMT-4)" (ou o que o fuso configurado valer no
 * momento) - calculado a partir de config('timezone') (config/config.php,
 * aplicado em config/bootstrap.php via date_default_timezone_set()) em vez
 * de fixo no texto, pra nao ficar errado se o fuso do servidor mudar um dia.
 *
 * Chamado no cabecalho de cada coluna/lista de horario (uma vez so'), nao
 * dentro de formatarDataHora() - repetir "(GMT-4)" em toda linha de uma
 * tabela com dezenas de linhas so' teria poluido a tela.
 */
function sufixoFusoHorario()
{
    $horasOffset = intdiv((int) date('O'), 100);

    return '(GMT' . sprintf('%+d', $horasOffset) . ')';
}

/**
 * Fase 24: link_meet (mentoria/oficina) e' texto livre digitado por
 * administrador/suporte e renderizado como href em telas de outros perfis
 * (participante) - sem essa checagem, um "link" tipo "javascript:..."
 * viraria XSS armazenado ao ser clicado. Valida tanto na gravacao
 * (Controllers) quanto de novo aqui na exibicao, como defesa em profundidade.
 */
function linkHttpValido($link)
{
    return is_string($link) && $link !== '' && preg_match('#^https?://#i', $link) === 1;
}

/**
 * Fase 31 (convencao de UI, ver README "Convencoes de UI"): grava a
 * mensagem pos-redirect (`$_SESSION['flash']`) junto com seu tipo
 * semantico. Chamar so' $_SESSION['flash'] = '...' direto (sem passar por
 * uma destas 3) continua valendo em todo o codigo legado - classeFlash()
 * trata "sem tipo" como sucesso, entao nada quebra. Em codigo novo, sempre
 * usar a funcao certa em vez de atribuir a sessao na mao.
 */
function flashSucesso($mensagem)
{
    $_SESSION['flash'] = $mensagem;
    $_SESSION['flash_tipo'] = 'sucesso';
}

function flashAlerta($mensagem)
{
    $_SESSION['flash'] = $mensagem;
    $_SESSION['flash_tipo'] = 'alerta';
}

function flashErro($mensagem)
{
    $_SESSION['flash'] = $mensagem;
    $_SESSION['flash_tipo'] = 'erro';
}

/**
 * Classe CSS do tipo da flash atual - default 'sucesso' quando ninguem
 * setou flash_tipo (mensagem antiga, so' com $_SESSION['flash'] = '...').
 * Efeito colateral proposital: desmarca flash_tipo, espelhando o mesmo
 * padrao ja usado pra $_SESSION['flash'] (ver views que chamam isso dentro
 * do proprio echo, unset() do texto e da classe juntos).
 */
function classeFlash()
{
    $tipo = isset($_SESSION['flash_tipo']) ? $_SESSION['flash_tipo'] : 'sucesso';
    unset($_SESSION['flash_tipo']);

    return in_array($tipo, ['sucesso', 'alerta', 'erro'], true) ? $tipo : 'sucesso';
}

/**
 * Fase 31 (Auditoria de Seguranca): campo oculto com o token CSRF da sessao
 * atual, pra incluir logo apos a abertura de todo <form method="post">.
 * Validado centralmente em Router::despachar() pra qualquer requisicao
 * nao-GET de usuario autenticado.
 */
function campoCsrf()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Fase 31: so administrador/suporte com e-mail institucional do Workspace
 * (@tjrr.jus.br) pode ser organizador (mentor/criador de oficina) de um
 * compromisso integrado ao Google Agenda - a Service Account usa Domain-Wide
 * Delegation, que so consegue impersonar contas do dominio institucional.
 * Chamado tanto na view (desabilitar o checkbox) quanto no controller/
 * GoogleCalendarSyncService (validacao real, nunca confiar so no client).
 */
function organizadorElegivelGoogle($email)
{
    return is_string($email) && preg_match('/@tjrr\.jus\.br$/i', trim($email)) === 1;
}

/**
 * Normaliza um nome pra comparacao (maiusculas, sem acento, sem espaco
 * duplicado). Usado sempre que um nome vindo de FORA precisa ser confrontado
 * com o nome de um participante cadastrado - hoje em dois pontos:
 * RequerimentoAdminController (nome do assinante devolvido pelo ITI, Fase 30)
 * e a captura de presenca do Meet (nome da conta Google de quem entrou na
 * sala, Fase 32, onde a API nao devolve e-mail e o nome e' a unica chave
 * disponivel).
 *
 * Nao e' garantia de acerto: "Joao Silva" no Google e "Joao Batista da Silva"
 * cadastrado nao casam. Quem chama precisa tratar "nao bateu" como resultado
 * legitimo, nunca como erro.
 */
function normalizarNomeParaComparacao($nome)
{
    if (!is_string($nome)) {
        return '';
    }

    $maiusculo = mb_strtoupper(trim($nome), 'UTF-8');
    $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT', $maiusculo);
    $semAcento = $transliterado !== false ? $transliterado : $maiusculo;

    return preg_replace('/\s+/', ' ', $semAcento);
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
