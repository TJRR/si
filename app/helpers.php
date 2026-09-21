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
 * Fase 41 (correcao pos-teste de fumaca): deteccao simples de dispositivo
 * movel por User-Agent. Usada so' para decidir APARENCIA (celular ve' desde
 * ja' o visual do aplicativo mesmo antes de ter conta; computador ve' o
 * site) - nunca para decisao de seguranca/autorizacao. Ver ehContextoApp()
 * logo abaixo para o caso de quem acessa de um COMPUTADOR com o PWA ja'
 * instalado (User-Agent de desktop normal, mas a janela ja' esta' em modo
 * app - precisa do mesmo visual).
 */
function ehDispositivoMovel()
{
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    return preg_match('/Android|iPhone|iPad|iPod|Mobile|Windows Phone/i', $userAgent) === 1;
}

/**
 * Fase 41 (correcao pos-teste de fumaca): reconhece quem esta' navegando
 * dentro do aplicativo instalado, em QUALQUER dispositivo (nao so' celular)
 * - ex.: alguem que instalou o PWA no COMPUTADOR, cuja janela ja' abre sem
 * barra de enderelo (display: standalone), mas cujo User-Agent continua
 * sendo de desktop comum. O manifesto (EventoAppController::manifesto())
 * declara start_url com '?modo=app' - toda vez que o navegador abre o app
 * pelo icone instalado, essa marca chega na primeira requisicao; aqui ela e'
 * gravada na sessao (bandeira persistente) para continuar valendo mesmo
 * depois, em telas sem esse parametro (ex.: login, apos a sessao expirar
 * dentro do app ja' instalado).
 */
function ehContextoApp()
{
    if (isset($_GET['modo']) && $_GET['modo'] === 'app') {
        $_SESSION['contexto_app'] = true;
    }

    return ehDispositivoMovel() || !empty($_SESSION['contexto_app']);
}

/**
 * Fase 41 (correcao pos-teste de fumaca): decide se o texto sobre uma cor
 * de fundo configurada pelo Admin deve ser claro ou escuro, calculando a
 * luminancia relativa da cor (formula simplificada, sem correcao gamma -
 * suficiente para uma decisao binaria de contraste). Evita fixar no codigo
 * "essa cor de fundo sempre usa texto escuro" - o Admin escolhe livremente
 * a cor (ex.: "Cor de destaque do aplicativo" em Configuracoes > Tema) sem
 * precisar tambem escolher a cor do texto, nem correr o risco de texto
 * ilegivel se escolher uma cor escura.
 */
function corContrastante($corHex, $corClara = '#fff', $corEscura = '#222')
{
    $hex = ltrim((string) $corHex, '#');

    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return $corEscura;
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminancia = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

    return $luminancia > 0.55 ? $corEscura : $corClara;
}

/**
 * Fase 41: URL de um dos 3 icones do aplicativo web instalavel (PWA) do
 * Evento ('icon-192.png', 'icon-512.png' ou 'icon-512-maskable.png') - com
 * fallback pro icone padrao versionado em assets/img/pwa/ quando o
 * Administrador ainda nao enviou um proprio (Configuracoes Gerais), e
 * cache-buster (?v=) baseado em configuracoes_sistema.icone_app_atualizado_em
 * para o navegador nao continuar servindo um icone antigo apos a troca.
 * Centraliza a logica usada por EventoAppController::manifesto(),
 * app/Views/layout.php (apple-touch-icon) e o app-bar das telas do evento -
 * sem isso, os 3 pontos driftariam entre si a cada ajuste futuro.
 */
function iconeAppUrl($nomeArquivo)
{
    $configuracao = (new \App\Repositories\ConfiguracaoSistemaRepository())->buscar();
    $temIconePersonalizado = $configuracao !== false && !empty($configuracao['icone_app_atualizado_em']);
    $versao = $temIconePersonalizado ? strtotime($configuracao['icone_app_atualizado_em']) : 1;
    $base = $temIconePersonalizado
        ? config('base_path') . '/assets/uploads/conteudo/icone-app/'
        : config('base_path') . '/assets/img/pwa/';

    return $base . $nomeArquivo . '?v=' . $versao;
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
 * Os campos Telefone/WhatsApp de Configuracoes > Contato sao digitados no
 * formato nacional ("(95) 3198-4194"), mas tanto wa.me quanto tel: exigem
 * so' digitos e COM o codigo do pais - sem ele o link nasce invalido.
 * Normaliza uma vez so', pros dois links (ver linkWhatsApp/linkTelefone) e
 * pros e-mails automaticos, em vez de cada ponto reinventar a conta.
 *
 * 10 ou 11 digitos = formato nacional (DDD + numero, com ou sem o 9 do
 * celular): recebe o 55. De 12 a 15 digitos = ja veio com codigo do pais
 * (limite do E.164), passa intocado. Qualquer outra quantidade e' entrada
 * que nao da' pra interpretar com seguranca - devolve null, e quem chama
 * mostra o numero sem virar link em vez de emitir um href quebrado.
 */
function telefoneComCodigoPais($numero)
{
    $digitos = preg_replace('/\D/', '', (string) $numero);
    $tamanho = strlen($digitos);

    if ($tamanho === 10 || $tamanho === 11) {
        return '55' . $digitos;
    }

    if ($tamanho >= 12 && $tamanho <= 15) {
        return $digitos;
    }

    return null;
}

/**
 * Link de conversa no WhatsApp a partir do numero cadastrado em
 * Configuracoes > Contato. Usado pelo rodape da home, pelo botao flutuante
 * de suporte e pela assinatura dos e-mails automaticos - antes cada um
 * montava o seu (dois com o numero fixo no codigo, e com digitos
 * divergentes entre si).
 */
function linkWhatsApp($numero)
{
    $digitos = telefoneComCodigoPais($numero);

    return $digitos !== null ? 'https://wa.me/' . $digitos : null;
}

/**
 * Link de discagem (tel:) em E.164, com o "+" - e' o que faz o celular ou o
 * softphone discar direto, sem a pessoa ter que corrigir o numero na mao.
 */
function linkTelefone($numero)
{
    $digitos = telefoneComCodigoPais($numero);

    return $digitos !== null ? 'tel:+' . $digitos : null;
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
 * Logo de Concurso GLOBAL/default do sistema (usado no topbar do painel,
 * paginas convidadas, e como fallback da home publica quando a edicao ativa
 * nao tem logo proprio). Fase 48B: fonte de verdade passou a ser a logo de
 * Concurso do TEMA ativo (usuario autenticado, se houver, senao o tema
 * padrao do sistema - TemaVisualRepository::resolverAtivo(), mesma logica
 * de app/Views/layout.php), no lugar da configuracao unica que existia em
 * configuracoes_visuais.logo_path. Mantem o fallback antigo (conteudos_site,
 * chave 'logo_site') por compatibilidade com o logo enviado em producao
 * antes desta fase, para quando nenhum tema tiver logo de Concurso definida.
 */
/**
 * Fase 49 (achado do usuario): $contextoEvento faz a mesma escolha usar
 * logo_evento_path em vez de logo_concurso_path - usado nas telas de
 * participante/avaliador de Trabalhos (fora do aplicativo instalavel, mas
 * ainda assim experiencia do Evento, nunca do Concurso). Sem o parametro
 * (uso normal, telas do Concurso), comportamento identico ao de sempre.
 */
function logoAtual($contextoEvento = false)
{
    $temaAtivo = (new \App\Repositories\TemaVisualRepository())->resolverAtivo(\App\Core\Auth::autenticado() ? \App\Core\Auth::usuarioId() : null);

    if ($contextoEvento && $temaAtivo !== null && !empty($temaAtivo['logo_evento_path'])) {
        return config('base_path') . '/assets/' . $temaAtivo['logo_evento_path'];
    }

    if ($temaAtivo !== null && !empty($temaAtivo['logo_concurso_path'])) {
        return config('base_path') . '/assets/' . $temaAtivo['logo_concurso_path'];
    }

    $logoConteudo = (new \App\Repositories\ConteudoSiteRepository())->buscarPorChave('logo_site');

    return $logoConteudo !== null && !empty($logoConteudo['arquivo_path'])
        ? config('base_path') . '/assets/' . $logoConteudo['arquivo_path']
        : config('base_path') . '/assets/img/logo-padrao.png';
}

/**
 * Nome da instituição, configurável pelo Admin (Configurações > Identidade
 * institucional) - nunca escrever "TJRR" fixo no código; usar esta função.
 */
function nomeInstituicao()
{
    $configuracao = (new \App\Repositories\ConfiguracaoSistemaRepository())->buscar();

    return $configuracao !== false && !empty($configuracao['instituicao']) ? $configuracao['instituicao'] : 'TJRR';
}

/**
 * Nome da unidade responsável, configurável pelo Admin (Configurações >
 * Identidade institucional) - nunca escrever "NPI" fixo no código; usar
 * esta função.
 */
function nomeUnidadeResponsavel()
{
    $configuracao = (new \App\Repositories\ConfiguracaoSistemaRepository())->buscar();

    return $configuracao !== false && !empty($configuracao['unidade_responsavel']) ? $configuracao['unidade_responsavel'] : 'NPI';
}

/**
 * Nome completo da instituição (Configurações > Identidade institucional),
 * para textos formais (rodapé, e-mails de acesso) - use nomeInstituicao()
 * para a sigla, em menções curtas.
 */
function nomeInstituicaoCompleto()
{
    $configuracao = (new \App\Repositories\ConfiguracaoSistemaRepository())->buscar();

    return $configuracao !== false && !empty($configuracao['instituicao_nome_completo'])
        ? $configuracao['instituicao_nome_completo']
        : 'Tribunal de Justiça do Estado de Roraima';
}

/**
 * Nome completo da unidade responsável (Configurações > Identidade
 * institucional) - use nomeUnidadeResponsavel() para a sigla.
 */
function nomeUnidadeResponsavelCompleto()
{
    $configuracao = (new \App\Repositories\ConfiguracaoSistemaRepository())->buscar();

    return $configuracao !== false && !empty($configuracao['unidade_responsavel_nome_completo'])
        ? $configuracao['unidade_responsavel_nome_completo']
        : 'Núcleo de Projetos e Inovação';
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

/**
 * Sanitiza o HTML gravado pelo editor rico (Fase 18) por blocklist: remove
 * so' vetores de execucao de JavaScript (tags de script/embed/svg, atributos
 * on*, URLs fora da allowlist de esquemas seguros), preservando tudo o
 * resto (estilo inline de cor/fonte/tamanho/alinhamento, imagens, links,
 * barra decorativa) - decisao de 28/08/2026, ver discussao de seguranca
 * sobre XSS armazenado no campo resumo_destaque (resultados_trilha).
 * Chamada no momento de SALVAR (nunca na exibicao, que continua echo
 * direto).
 *
 * Revisao de seguranca (mesmo dia) apontou duas lacunas do blocklist
 * original, corrigidas aqui: (1) 'href'/'src' sozinhos nao pegam atributos
 * namespaced tipo 'xlink:href' dentro de <svg> - agora compara pelo nome
 * LOCAL (depois do ':'), alem de remover <svg>/<math>/<style>/<template>
 * inteiras (nenhuma e' usada pelo editor, entao remover nao quebra nada
 * real). (2) blacklist de esquema (javascript:/vbscript:) trocada por
 * ALLOWLIST (http/https/mailto/tel/relativo) - fecha qualquer esquema
 * perigoso que uma blacklist pudesse deixar passar (data:, etc.), sem
 * depender de prever cada variante.
 *
 * Usa DOMDocument (extensao 'dom', ja garantida no ambiente porque
 * dompdf/dompdf a exige) - sem lib nova, sem build step, mesma filosofia
 * do editor-rico.js.
 */
function sanitizarHtmlRico($html)
{
    $html = (string) $html;

    if (trim($html) === '') {
        return $html;
    }

    $tagsRemovidas = ['script', 'iframe', 'object', 'embed', 'applet', 'link', 'meta', 'base', 'form', 'svg', 'math', 'style', 'template'];
    $atributosUrl = ['href', 'src', 'action', 'formaction', 'background', 'poster'];

    $documento = new DOMDocument();
    $usoInternoAnterior = libxml_use_internal_errors(true);
    $documento->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($usoInternoAnterior);

    $raiz = $documento->getElementsByTagName('div')->item(0);

    if ($raiz === null) {
        return $html;
    }

    foreach ($tagsRemovidas as $tag) {
        $elementos = $documento->getElementsByTagName($tag);
        while ($elementos->length > 0) {
            $elemento = $elementos->item(0);
            if ($elemento->parentNode !== null) {
                $elemento->parentNode->removeChild($elemento);
            } else {
                break;
            }
        }
    }

    $xpath = new DOMXPath($documento);

    foreach ($xpath->query('//*') as $elemento) {
        if (!($elemento instanceof DOMElement) || !$elemento->hasAttributes()) {
            continue;
        }

        $atributosParaRemover = [];

        foreach ($elemento->attributes as $atributo) {
            // Nome LOCAL (sem prefixo de namespace) - cobre 'xlink:href',
            // 'xml:href' etc. tratados como se fossem 'href' puro.
            $partesNome = explode(':', $atributo->name);
            $nomeLocal = strtolower(end($partesNome));
            $valorAtributo = $atributo->value;

            if (strpos($nomeLocal, 'on') === 0) {
                $atributosParaRemover[] = $atributo->name;
                continue;
            }

            if (in_array($nomeLocal, $atributosUrl, true) && !urlComEsquemaPermitido($valorAtributo)) {
                $atributosParaRemover[] = $atributo->name;
                continue;
            }

            if ($nomeLocal === 'style' && preg_match('/expression\s*\(|url\s*\(\s*[\'"]?\s*(javascript|vbscript|data):/i', $valorAtributo)) {
                $atributosParaRemover[] = $atributo->name;
            }
        }

        foreach ($atributosParaRemover as $nomeAtributo) {
            $elemento->removeAttribute($nomeAtributo);
        }
    }

    $htmlSanitizado = '';
    foreach ($raiz->childNodes as $no) {
        $htmlSanitizado .= $documento->saveHTML($no);
    }

    return $htmlSanitizado;
}

/**
 * Allowlist de esquemas de URL (em vez de blacklist): mais seguro porque
 * nao depende de prever cada variante perigosa (javascript:, vbscript:,
 * data:, e o que mais surgir) - so' passa o que reconhecemos como seguro.
 * URL relativa (sem esquema, ex.: '/assets/x.png', 'pagina.html', '#ancora')
 * tambem e' permitida.
 */
function urlComEsquemaPermitido($valor)
{
    $normalizado = trim(preg_replace('/[\s\x00-\x1F]+/', '', (string) $valor));

    if ($normalizado === '') {
        return true;
    }

    if (!preg_match('/^([a-z][a-z0-9+.-]*):/i', $normalizado, $correspondencia)) {
        // Sem "esquema:" no inicio - relativo, ancora, etc. Seguro.
        return true;
    }

    $esquemasPermitidos = ['http', 'https', 'mailto', 'tel'];

    return in_array(strtolower($correspondencia[1]), $esquemasPermitidos, true);
}
