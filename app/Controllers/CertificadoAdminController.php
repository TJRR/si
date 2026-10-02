<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\CertificadoConfigRepository;
use App\Repositories\CertificadoRepository;
use App\Repositories\MidiaRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoApresentacaoRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\CertificadoAvisoService;
use App\Services\CertificadoElegibilidadeService;
use App\Services\CertificadoEmissaoService;
use App\Services\CertificadoFundoService;
use App\Services\CertificadoPdfService;
use App\Services\CertificadoTextoService;
use App\Services\ImagemService;
use App\Validation\CpfValidador;

/**
 * Fase 59: Certificados (no da arvore irmao de Atividades, Trabalhos,
 * Estandes, Conexoes, Divulgacao, Bonus, Pesquisa, Competicoes e
 * Gamificacao). Suporte le, como nos demais modulos do Evento; toda gravacao
 * exige Administrador.
 *
 * Quatro abas: Elegiveis (quem tem direito e o que falta), Emitidos (os
 * documentos guardados), Apresentacoes (a marca de trabalho apresentado,
 * exigida pela norma de submissao de trabalhos do evento) e Configuracoes.
 *
 * A aba Apresentacoes vive aqui, e nao em Trabalhos, por duas razoes: a
 * marca existe para o certificado de apresentacao e nao tem outro
 * consumidor, e TrabalhoAdminController e' codigo em producao que esta fase
 * nao precisa tocar.
 *
 * As duas travas de emissao sao diferentes de proposito: o fim do evento vale
 * para todos, inclusive para o Administrador, porque emitir antes congelaria
 * carga horaria incompleta num documento que nunca mais muda; a liberacao
 * vale so' para o participante, para a organizacao poder ter os documentos
 * prontos para a cerimonia antes de abrir a emissao.
 */
class CertificadoAdminController extends Controller
{
    /**
     * Quantos elegiveis a tela mostra por pagina. A lista vem apurada em
     * memoria (uma consulta agregada por grandeza, nao uma por pessoa), e e'
     * recortada aqui.
     */
    const POR_PAGINA = 50;

    private $eventos;
    private $config;
    private $certificados;
    private $apresentacoes;
    private $elegibilidade;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new CertificadoConfigRepository();
        $this->certificados = new CertificadoRepository();
        $this->apresentacoes = new TrabalhoApresentacaoRepository();
        $this->elegibilidade = new CertificadoElegibilidadeService();
    }

    private function eventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    private function certificadoDoEventoOu404(array $evento, $certificadoId)
    {
        $certificado = $this->certificados->buscarPorId($certificadoId);

        if ($certificado === null || (int) $certificado['evento_id'] !== (int) $evento['id']) {
            http_response_code(404);
            exit('Certificado não encontrado neste evento.');
        }

        return $certificado;
    }

    /**
     * Primeira aba: quem tem direito a que, com o que ja' foi emitido. E'
     * dela que sai a emissao em lote.
     */
    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $config = $this->config->vigente($id);
        $filtros = $this->filtrosDosElegiveis();

        $dossies = $this->dossiesFiltrados($evento, $config, $filtros);
        $pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
        $total = count($dossies);

        $this->renderizar('admin/certificados/index', [
            'evento' => $evento,
            'config' => $config,
            'exigencias' => CertificadoElegibilidadeService::exigenciasEmVigor($config),
            'eventoTerminou' => $this->elegibilidade->eventoTerminou($evento),
            'emissaoAberta' => $this->elegibilidade->emissaoAbertaAoParticipante($evento, $config),
            'dossies' => array_slice($dossies, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA, true),
            'total' => $total,
            'pagina' => $pagina,
            'porPagina' => self::POR_PAGINA,
            'tetoLote' => CertificadoEmissaoService::TETO_LOTE,
            'filtros' => $filtros,
            'textosFaltando' => $this->textosFaltando($config),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Certificados: ' . $evento['nome'], ['tipo' => 'certificados', 'id' => $id]);
    }

    /**
     * Segunda aba: os documentos ja' emitidos, com baixa e cancelamento.
     */
    public function emitidos($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $filtros = $this->filtrosDosEmitidos();
        $pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;

        $this->renderizar('admin/certificados/emitidos', [
            'evento' => $evento,
            'certificados' => $this->certificados->listarPorEvento(
                $id,
                $filtros,
                self::POR_PAGINA,
                ($pagina - 1) * self::POR_PAGINA
            ),
            'total' => $this->certificados->contarPorEvento($id, $filtros),
            'resumo' => $this->certificados->resumoPorTipo($id),
            'pagina' => $pagina,
            'porPagina' => self::POR_PAGINA,
            'tetoLote' => CertificadoEmissaoService::TETO_LOTE,
            'filtros' => $filtros,
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Certificados emitidos: ' . $evento['nome'], ['tipo' => 'certificadosEmitidos', 'id' => $id]);
    }

    /**
     * Terceira aba: a marca de trabalho efetivamente apresentado. Sem ela
     * nenhum autor recebe certificado de apresentacao.
     */
    public function apresentacoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $trabalhos = $this->apresentacoes->listarSelecionadosDoEvento($id);
        $apresentados = 0;

        foreach ($trabalhos as $trabalho) {
            if ($trabalho['apresentado_em'] !== null) {
                $apresentados++;
            }
        }

        $this->renderizar('admin/certificados/apresentacoes', [
            'evento' => $evento,
            'trabalhos' => $trabalhos,
            'apresentados' => $apresentados,
            'podeEditar' => Auth::possuiPerfil('administrador'),
            'casasDecimais' => \App\Repositories\TrabalhoConfigRepository::casasDecimais((new \App\Repositories\TrabalhoConfigRepository())->buscarPorEvento($id)),
        ], 'Apresentações de trabalho: ' . $evento['nome'], ['tipo' => 'certificadosApresentacoes', 'id' => $id]);
    }

    /**
     * Quarta aba: a regua, os textos, os fundos e a liberacao.
     */
    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $erros = [];
        $valores = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $valores = $this->dadosDaConfiguracao();
            $erros = $this->validarConfiguracao($valores);

            if ($erros === []) {
                $this->config->salvar($id, $valores);
                flashSucesso('Configurações dos certificados salvas.');
                $this->redirecionar('certificados/configuracoes/' . $id);
                return;
            }

            flashErro('Confira os campos destacados.');
        }

        $config = $valores !== null ? array_merge($this->config->vigente($id), $valores) : $this->config->vigente($id);

        // A pasta "Fundo Certificados" e a arte que acompanha o sistema
        // nascem aqui, na primeira abertura da tela, e nunca em migration
        // (dado de negocio nao entra por migration). So' o Administrador
        // grava: para o Suporte, que ve' a tela em leitura, o seletor nem
        // aparece.
        if (Auth::possuiPerfil('administrador')) {
            (new CertificadoFundoService())->garantirPasta(Auth::usuarioId());
        }

        $this->renderizar('admin/certificados/configuracoes', [
            'evento' => $evento,
            'config' => $config,
            'erros' => $erros,
            'eventoTerminou' => $this->elegibilidade->eventoTerminou($evento),
            'emissaoAberta' => $this->elegibilidade->emissaoAbertaAoParticipante($evento, $config),
            'pendentesAviso' => $this->elegibilidade->emissaoAbertaAoParticipante($evento, $config)
                ? (new CertificadoAvisoService())->contarPendentes($evento, $config)
                : 0,
            'palavrasChave' => [
                CertificadoElegibilidadeService::TIPO_EVENTO => CertificadoTextoService::palavrasChaveDoTipo(
                    CertificadoElegibilidadeService::TIPO_EVENTO
                ),
                CertificadoElegibilidadeService::TIPO_ATIVIDADE => CertificadoTextoService::palavrasChaveDoTipo(
                    CertificadoElegibilidadeService::TIPO_ATIVIDADE
                ),
                CertificadoElegibilidadeService::TIPO_APRESENTACAO => CertificadoTextoService::palavrasChaveDoTipo(
                    CertificadoElegibilidadeService::TIPO_APRESENTACAO
                ),
            ],
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Configurações dos certificados: ' . $evento['nome'], ['tipo' => 'certificadosConfiguracoes', 'id' => $id]);
    }

    /**
     * Abre ou fecha a emissao para o participante. O fim do evento continua
     * sendo conferido na emissao, entao liberar antes nao faz ninguem emitir
     * cedo: so' deixa a chave pronta.
     */
    public function liberar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $abrir = !empty($_POST['abrir']);

        $this->config->definirLiberacao($id, $abrir ? date('Y-m-d H:i:s') : null, Auth::usuarioId());

        if ($abrir) {
            flashSucesso($this->elegibilidade->eventoTerminou($evento)
                ? 'Emissão aberta: quem tem direito já pode retirar o certificado no aplicativo.'
                : 'Emissão aberta. Ela passa a valer para o participante depois do último dia do evento.');

            if (!empty($_POST['avisar'])) {
                $this->avisarElegiveis($evento);
            }
        } else {
            flashAlerta('Emissão fechada para o participante. Os certificados já emitidos continuam valendo.');
        }

        $this->redirecionar('certificados/configuracoes/' . $id);
    }

    /**
     * Botao "Avisar quem ainda nao foi avisado" da tela de Configuracoes.
     */
    public function avisar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->avisarElegiveis($evento);
        }

        $this->redirecionar('certificados/configuracoes/' . (int) $evento['id']);
    }

    /**
     * A emissao ja esta' confirmada quando isto roda: uma falha ao criar os
     * avisos nunca a desfaz, so' avisa o Administrador.
     */
    private function avisarElegiveis(array $evento)
    {
        $config = $this->config->vigente((int) $evento['id']);
        $servico = new CertificadoAvisoService();

        if (!$servico->podeAvisar($evento, $config)) {
            flashAlerta('O aviso aos participantes só pode sair depois do último dia do evento, com o módulo ligado e a emissão aberta. Use o botão "Avisar quem ainda não foi avisado" quando o certificado já puder ser retirado.');
            return;
        }

        try {
            $resultado = $servico->avisar($evento, $config, Auth::usuarioId());
        } catch (\Throwable $e) {
            error_log('[Certificados] falha ao avisar os elegiveis do evento ' . (int) $evento['id'] . ': ' . $e->getMessage());
            flashErro('Não foi possível criar os avisos agora. A emissão não foi alterada; tente avisar de novo pelo botão da tela.');
            return;
        }

        if ($resultado['pessoas'] === 0) {
            flashSucesso('Ninguém a avisar: todos os certificados disponíveis já foram avisados.');
            return;
        }

        flashSucesso('Aviso criado para ' . $resultado['pessoas'] . ' pessoa(s), sobre ' . $resultado['documentos'] . ' certificado(s): no aplicativo agora e por e-mail pela fila de envio (dez por minuto).');
    }

    /**
     * Emite todos os itens de uma pessoa. Usado pelo botao de cada linha.
     */
    public function emitir($eventoId)
    {
        $evento = $this->prepararEmissao($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $config = $this->config->vigente($id);
        $chave = isset($_POST['chave']) ? trim((string) $_POST['chave']) : '';
        $dossies = $this->elegibilidade->dossiesDoEvento($evento, $config);

        if (!isset($dossies[$chave])) {
            flashAlerta('Não foi encontrado quem emitir. Recarregue a lista e tente outra vez.');
            $this->redirecionar('certificados/index/' . $id);
            return;
        }

        $resultado = (new CertificadoEmissaoService())->emitirDossie($evento, $config, $dossies[$chave], Auth::usuarioId());
        $this->avisarResultado($resultado);
        $this->redirecionar('certificados/index/' . $id);
    }

    /**
     * Emite os itens das pessoas marcadas na lista, ate' o teto da chamada.
     */
    public function emitirSelecionados($eventoId)
    {
        $evento = $this->prepararEmissao($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $config = $this->config->vigente($id);
        $chaves = isset($_POST['chaves']) && is_array($_POST['chaves']) ? $_POST['chaves'] : [];

        if ($chaves === []) {
            flashAlerta('Marque ao menos uma pessoa para emitir.');
            $this->redirecionar('certificados/index/' . $id);
            return;
        }

        // A lista apurada e' a fonte de verdade: chave que nao esteja nela
        // (de outro evento, ou de quem perdeu o direito desde que a tela
        // abriu) simplesmente nao alcanca nada.
        $dossies = $this->elegibilidade->dossiesDoEvento($evento, $config);
        $escolhidos = [];

        foreach ($chaves as $chave) {
            $chave = trim((string) $chave);

            if (isset($dossies[$chave])) {
                $escolhidos[$chave] = $dossies[$chave];
            }
        }

        $resultado = (new CertificadoEmissaoService())->emitirDossies($evento, $config, $escolhidos, Auth::usuarioId());
        $this->avisarResultado($resultado);
        $this->redirecionar('certificados/index/' . $id);
    }

    /**
     * Entrega o arquivo guardado de um certificado. Cancelado nao e' servido:
     * e' o que o cancelamento significa.
     */
    public function baixar($eventoId, $certificadoId = null)
    {
        $evento = $this->eventoOu404($eventoId);
        $certificado = $this->certificadoDoEventoOu404($evento, $certificadoId);

        if ($certificado['cancelado_em'] !== null) {
            http_response_code(404);
            exit('Este certificado foi cancelado em ' . formatarData($certificado['cancelado_em']) . '.');
        }

        ArquivoPrivadoService::servir($certificado['arquivo_path'], $this->nomeDoArquivo($certificado));
    }

    /**
     * Baixa os certificados marcados num PDF unico, um por folha.
     *
     * Um arquivo so', e nao um pacote compactado, porque a extensao de
     * compactacao do PHP nao esta' instalada no ambiente e nao ha' como
     * confirmar que exista em producao. A FPDI, que o projeto ja' usa nos
     * Anais, resolve sem dependencia nova.
     */
    public function baixarSelecionados($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        $linhas = $this->certificados->listarPorIds($id, $this->inteirosDoLote($ids));

        if ($linhas === []) {
            flashAlerta('Marque ao menos um certificado para baixar.');
            $this->redirecionar('certificados/emitidos/' . $id);
            return;
        }

        $caminhos = [];

        foreach (array_slice($linhas, 0, CertificadoEmissaoService::TETO_LOTE) as $linha) {
            if ($linha['cancelado_em'] === null) {
                $caminhos[] = $linha['arquivo_path'];
            }
        }

        $conteudo = $caminhos !== [] ? (new CertificadoPdfService())->juntar($caminhos) : null;

        if ($conteudo === null) {
            flashErro('Não foi possível juntar os certificados marcados. Nenhum arquivo válido foi encontrado.');
            $this->redirecionar('certificados/emitidos/' . $id);
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="certificados-evento-' . $id . '.pdf"');
        header('Content-Length: ' . strlen($conteudo));
        echo $conteudo;
        exit;
    }

    public function cancelar($eventoId, $certificadoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $certificado = $this->certificadoDoEventoOu404($evento, $certificadoId);
        $motivo = isset($_POST['motivo']) ? trim((string) $_POST['motivo']) : '';

        if ($motivo === '') {
            flashErro('Escreva o motivo do cancelamento.');
            $this->redirecionar('certificados/emitidos/' . (int) $evento['id']);
            return;
        }

        if ($this->certificados->cancelar((int) $certificado['id'], Auth::usuarioId(), $motivo)) {
            flashSucesso('Certificado cancelado. A página de conferência passa a informar o cancelamento.');
        } else {
            flashAlerta('Este certificado já estava cancelado.');
        }

        $this->redirecionar('certificados/emitidos/' . (int) $evento['id']);
    }

    public function cancelarSelecionados($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $motivo = isset($_POST['motivo']) ? trim((string) $_POST['motivo']) : '';
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];

        if ($motivo === '') {
            flashErro('Escreva o motivo do cancelamento.');
            $this->redirecionar('certificados/emitidos/' . $id);
            return;
        }

        if ($this->inteirosDoLote($ids) === []) {
            flashAlerta('Marque ao menos um certificado para cancelar.');
            $this->redirecionar('certificados/emitidos/' . $id);
            return;
        }

        $alteradas = $this->certificados->cancelarEmLote($id, $ids, Auth::usuarioId(), $motivo);

        if ($alteradas > 0) {
            flashSucesso($alteradas . ' certificado(s) cancelado(s).');
        } else {
            flashAlerta('Nenhum dos certificados marcados estava válido.');
        }

        $this->redirecionar('certificados/emitidos/' . $id);
    }

    public function marcarApresentados($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        $observacao = isset($_POST['observacao']) ? trim((string) $_POST['observacao']) : '';

        if ($this->inteirosDoLote($ids) === []) {
            flashAlerta('Marque ao menos um trabalho.');
            $this->redirecionar('certificados/apresentacoes/' . $id);
            return;
        }

        $marcadas = $this->apresentacoes->marcarEmLote($id, $ids, Auth::usuarioId(), $observacao);

        if ($marcadas > 0) {
            flashSucesso($marcadas . ' trabalho(s) marcado(s) como apresentado(s). Os autores passam a ter direito ao certificado de apresentação.');
        } else {
            flashAlerta('Nenhuma marca nova: os trabalhos escolhidos já estavam marcados.');
        }

        $this->redirecionar('certificados/apresentacoes/' . $id);
    }

    public function desmarcarApresentados($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];

        if ($this->inteirosDoLote($ids) === []) {
            flashAlerta('Marque ao menos um trabalho.');
            $this->redirecionar('certificados/apresentacoes/' . $id);
            return;
        }

        $apagadas = $this->apresentacoes->desmarcarEmLote($id, $ids, Auth::usuarioId());

        if ($apagadas > 0) {
            flashSucesso($apagadas . ' marca(s) retirada(s). Certificado de apresentação já emitido continua valendo: para desfazer, cancele o documento na aba "Emitidos".');
        } else {
            flashAlerta('Nenhum dos trabalhos escolhidos estava marcado.');
        }

        $this->redirecionar('certificados/apresentacoes/' . $id);
    }

    /**
     * Fase 59: o que o seletor de plano de fundo mostra quando abre, em JSON.
     * Sem parametro de pasta, abre na pasta "Fundo Certificados", criada com
     * a arte que acompanha o sistema na primeira abertura desta tela.
     *
     * Leitura, entao Suporte tambem alcanca: a tela dele e' somente leitura e
     * o seletor nem aparece, mas um pedido direto a este endereco nao expoe
     * nada que a Biblioteca de midia ja' nao mostre a esse perfil.
     */
    public function fundos($eventoId)
    {
        $this->eventoOu404($eventoId);
        $servico = new CertificadoFundoService();

        // A pasta so' e' CRIADA por quem pode gravar. Para o Suporte, que
        // alcanca esta leitura, a consulta usa a pasta que ja' existir: uma
        // rota de leitura nao grava nada.
        $pasta = Auth::possuiPerfil('administrador')
            ? $servico->garantirPasta(Auth::usuarioId())
            : $servico->buscarPasta();
        $pastaId = isset($_GET['pasta']) && $_GET['pasta'] !== ''
            ? (int) $_GET['pasta']
            : ($pasta !== null ? (int) $pasta['id'] : null);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'pastaAtual' => $pastaId,
            'pastas' => $servico->listarPastas(),
            'imagens' => $servico->listarImagens($pastaId),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Fase 59: recebe uma arte nova pelo proprio seletor e a guarda na pasta
     * de fundos da Biblioteca de midia, devolvendo o endereco dela em JSON
     * para o seletor ja' deixa-la escolhida.
     *
     * Usa o mesmo ImagemService da Biblioteca, com o mesmo redimensionamento
     * e a mesma conversao: a arte do certificado nao e' um tipo de arquivo
     * novo no sistema, e' uma imagem como as outras.
     */
    public function enviarFundo($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventoOu404($eventoId);
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['erro' => 'Escolha o arquivo de imagem a enviar.']);
            exit;
        }

        $servico = new CertificadoFundoService();
        $pasta = $servico->garantirPasta(Auth::usuarioId());

        try {
            // 2000 pixels no maior lado: uma folha A4 na horizontal impressa
            // a 150 pontos por polegada tem cerca de 1754, entao a arte nao
            // perde definicao e o arquivo nao fica desnecessariamente grande.
            $caminho = (new ImagemService())->salvar($_FILES['arquivo'], 'certificados', 2000, 2000);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['erro' => $e->getMessage()]);
            exit;
        }

        $nome = trim(isset($_POST['titulo']) ? (string) $_POST['titulo'] : '');

        (new MidiaRepository())->criar([
            'concurso_id' => null,
            'pasta_id' => $pasta !== null ? (int) $pasta['id'] : null,
            'arquivo_path' => $caminho,
            'tipo' => 'imagem',
            'alt_text' => 'Plano de fundo de certificado',
            'titulo' => $nome !== '' ? $nome : 'Fundo de certificado',
            'descricao' => null,
            'criado_por' => Auth::usuarioId(),
        ]);

        echo json_encode([
            'url' => config('base_path') . '/assets/' . $caminho,
            'titulo' => $nome !== '' ? $nome : 'Fundo de certificado',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Exportacao da lista de elegiveis, respeitando o filtro da tela.
     */
    public function exportarElegiveis($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $config = $this->config->vigente($id);
        $dossies = $this->dossiesFiltrados($evento, $config, $this->filtrosDosElegiveis());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="certificados-elegiveis-evento-' . $id . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.
        fputcsv($saida, [
            'Nome', 'Correio eletrônico', 'Documento', 'Condições', 'Atividades', 'Dias',
            'Carga horária', 'A que tem direito', 'Já emitidos',
        ], ';');

        foreach ($dossies as $dossie) {
            fputcsv($saida, [
                $dossie['nome'],
                (string) $dossie['email'],
                $this->documentoFormatado($dossie),
                (new CertificadoTextoService())->condicoesPorExtenso($dossie['condicoes']),
                (int) $dossie['atividades'],
                (int) $dossie['dias'],
                CertificadoElegibilidadeService::formatarCargaHoraria($dossie['minutos_total']),
                count($dossie['itens']),
                $dossie['emitidos'],
            ], ';');
        }

        fclose($saida);
        exit;
    }

    /**
     * Exportacao dos certificados emitidos, respeitando o filtro da tela.
     */
    public function exportarEmitidos($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $linhas = $this->certificados->listarPorEvento($id, $this->filtrosDosEmitidos());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="certificados-emitidos-evento-' . $id . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF");
        fputcsv($saida, [
            'Tipo', 'Nome', 'Correio eletrônico', 'Condições', 'Referência', 'Carga horária',
            'Código', 'Emitido em', 'Situação', 'Motivo do cancelamento',
        ], ';');

        foreach ($linhas as $linha) {
            fputcsv($saida, [
                $this->rotuloDoTipo($linha['tipo']),
                $linha['nome'],
                (string) $linha['usuario_email'],
                $linha['condicoes'],
                $linha['atividade_nome'] !== null ? $linha['atividade_nome'] : (string) $linha['trabalho_titulo'],
                $linha['carga_horaria_minutos'] !== null
                    ? CertificadoElegibilidadeService::formatarCargaHoraria($linha['carga_horaria_minutos'])
                    : '',
                $linha['codigo_verificacao'],
                formatarDataHora($linha['emitido_em']),
                $linha['cancelado_em'] === null ? 'Válido' : 'Cancelado',
                (string) $linha['motivo_cancelamento'],
            ], ';');
        }

        fclose($saida);
        exit;
    }

    /**
     * Rotulo de cada tipo, usado nas telas e nas exportacoes.
     */
    public static function rotuloDoTipo($tipo)
    {
        $rotulos = [
            CertificadoElegibilidadeService::TIPO_EVENTO => 'Participação no evento',
            CertificadoElegibilidadeService::TIPO_ATIVIDADE => 'Atividade',
            CertificadoElegibilidadeService::TIPO_APRESENTACAO => 'Apresentação de trabalho',
        ];

        return isset($rotulos[$tipo]) ? $rotulos[$tipo] : $tipo;
    }

    /**
     * Confere as duas travas antes de qualquer emissao pela organizacao e
     * devolve o evento, ou null quando recusou (com o aviso ja' dado).
     */
    private function prepararEmissao($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $config = $this->config->vigente($id);

        if ((int) $config['ativo'] !== 1) {
            flashErro('O módulo de certificados está desligado para este evento.');
            $this->redirecionar('certificados/configuracoes/' . $id);
            return null;
        }

        if (!$this->elegibilidade->eventoTerminou($evento)) {
            flashErro('A emissão começa depois do último dia do evento: até lá a carga horária ainda pode mudar, e o documento é guardado como foi emitido.');
            $this->redirecionar('certificados/index/' . $id);
            return null;
        }

        return $evento;
    }

    /**
     * Os dossies do evento com o filtro da tela aplicado, cada um com a
     * contagem do que ja' foi emitido.
     */
    private function dossiesFiltrados(array $evento, array $config, array $filtros)
    {
        $id = (int) $evento['id'];
        $dossies = $this->elegibilidade->dossiesDoEvento($evento, $config);
        $emitidosPorChave = [];

        foreach ($this->certificados->listarPorEvento($id) as $linha) {
            $emitidosPorChave[$linha['chave_unicidade']] = $linha;
        }

        $resultado = [];

        foreach ($dossies as $chave => $dossie) {
            if ($dossie['itens'] === []) {
                continue;
            }

            $emitidos = 0;

            foreach ($dossie['itens'] as $item) {
                if (isset($emitidosPorChave[$item['chave']])) {
                    $emitidos++;
                }
            }

            $dossie['emitidos'] = $emitidos;

            if (!$this->passaNoFiltro($dossie, $filtros)) {
                continue;
            }

            $resultado[$chave] = $dossie;
        }

        return $resultado;
    }

    private function passaNoFiltro(array $dossie, array $filtros)
    {
        if ($filtros['situacao'] === 'pendentes' && $dossie['emitidos'] >= count($dossie['itens'])) {
            return false;
        }

        if ($filtros['situacao'] === 'completos' && $dossie['emitidos'] < count($dossie['itens'])) {
            return false;
        }

        if ($filtros['condicao'] !== '' && !in_array($filtros['condicao'], $dossie['condicoes'], true)) {
            return false;
        }

        if ($filtros['busca'] !== '') {
            $busca = mb_strtolower($filtros['busca'], 'UTF-8');
            $alvo = mb_strtolower($dossie['nome'] . ' ' . (string) $dossie['email'], 'UTF-8');

            if (mb_strpos($alvo, $busca) === false) {
                return false;
            }
        }

        return true;
    }

    private function filtrosDosElegiveis()
    {
        return [
            'busca' => isset($_GET['busca']) ? trim((string) $_GET['busca']) : '',
            'condicao' => isset($_GET['condicao']) ? trim((string) $_GET['condicao']) : '',
            'situacao' => isset($_GET['situacao']) ? trim((string) $_GET['situacao']) : '',
        ];
    }

    private function filtrosDosEmitidos()
    {
        return [
            'busca' => isset($_GET['busca']) ? trim((string) $_GET['busca']) : '',
            'tipo' => isset($_GET['tipo']) ? trim((string) $_GET['tipo']) : '',
            'condicao' => isset($_GET['condicao']) ? trim((string) $_GET['condicao']) : '',
            'situacao' => isset($_GET['situacao']) ? trim((string) $_GET['situacao']) : '',
        ];
    }

    /**
     * Quais tipos de certificado ainda nao tem texto escrito. A tela avisa,
     * porque sem texto a emissao daquele tipo e' recusada - o sistema nao
     * inventa o conteudo de um documento da instituicao.
     */
    private function textosFaltando(array $config)
    {
        $faltando = [];

        foreach ([
            CertificadoElegibilidadeService::TIPO_EVENTO,
            CertificadoElegibilidadeService::TIPO_ATIVIDADE,
            CertificadoElegibilidadeService::TIPO_APRESENTACAO,
        ] as $tipo) {
            if (CertificadoEmissaoService::textoDoTipo($config, $tipo) === null) {
                $faltando[] = self::rotuloDoTipo($tipo);
            }
        }

        return $faltando;
    }

    private function dadosDaConfiguracao()
    {
        return [
            'ativo' => !empty($_POST['ativo']) ? 1 : 0,
            'min_atividades' => $this->numeroOuNulo('min_atividades'),
            'min_dias' => $this->numeroOuNulo('min_dias'),
            'min_horas' => $this->numeroOuNulo('min_horas'),
            'exige_credenciamento' => !empty($_POST['exige_credenciamento']) ? 1 : 0,
            'carga_horaria_avaliador_horas' => $this->numeroOuNulo('carga_horaria_avaliador_horas'),
            'texto_evento_html' => $this->textoRicoOuNulo('texto_evento_html'),
            'texto_atividade_html' => $this->textoRicoOuNulo('texto_atividade_html'),
            'texto_apresentacao_html' => $this->textoRicoOuNulo('texto_apresentacao_html'),
            'fundo_url' => $this->enderecoOuNulo('fundo_url'),
            'fundo_atividade_url' => $this->enderecoOuNulo('fundo_atividade_url'),
            'fundo_apresentacao_url' => $this->enderecoOuNulo('fundo_apresentacao_url'),
            'fundo_cor' => $this->corOuNulo('fundo_cor'),
            'fundo_atividade_cor' => $this->corOuNulo('fundo_atividade_cor'),
            'fundo_apresentacao_cor' => $this->corOuNulo('fundo_apresentacao_cor'),
        ];
    }

    private function validarConfiguracao(array $valores)
    {
        $erros = [];

        foreach (['min_atividades', 'min_dias', 'min_horas', 'carga_horaria_avaliador_horas'] as $campo) {
            if ($valores[$campo] !== null && $valores[$campo] <= 0) {
                $erros[$campo] = 'Escreva um número maior que zero ou deixe em branco.';
            }
        }

        $textos = [
            'texto_evento_html' => CertificadoElegibilidadeService::TIPO_EVENTO,
            'texto_atividade_html' => CertificadoElegibilidadeService::TIPO_ATIVIDADE,
            'texto_apresentacao_html' => CertificadoElegibilidadeService::TIPO_APRESENTACAO,
        ];
        $servico = new CertificadoTextoService();

        foreach ($textos as $campo => $tipo) {
            if ($valores[$campo] === null) {
                continue;
            }

            $desconhecidas = $servico->palavrasChaveDesconhecidas($valores[$campo], $tipo);

            if ($desconhecidas !== []) {
                $erros[$campo] = 'Palavra-chave que não existe neste tipo de certificado: '
                    . implode(', ', array_map(function ($chave) {
                        return '[[' . $chave . ']]';
                    }, $desconhecidas))
                    . '. Use o dicionário ao lado do campo.';
            }
        }

        return $erros;
    }

    private function numeroOuNulo($campo)
    {
        $valor = isset($_POST[$campo]) ? trim((string) $_POST[$campo]) : '';

        return $valor === '' ? null : (int) $valor;
    }

    private function textoRicoOuNulo($campo)
    {
        $valor = isset($_POST[$campo]) ? (string) $_POST[$campo] : '';

        return trim(strip_tags($valor, '<img>')) === '' && strpos($valor, '<img') === false
            ? null
            : sanitizarHtmlRico($valor);
    }

    private function enderecoOuNulo($campo)
    {
        $valor = isset($_POST[$campo]) ? trim((string) $_POST[$campo]) : '';

        return $valor === '' ? null : $valor;
    }

    /**
     * Cor de fundo em notacao hexadecimal, ou nulo. Valor fora do formato
     * nao vira cor inventada nem erro de tela: fica nulo, e a folha sai
     * branca. O seletor da tela so' manda formato valido; esta conferencia
     * existe para quem alterar o formulario.
     */
    private function corOuNulo($campo)
    {
        $valor = isset($_POST[$campo]) ? trim((string) $_POST[$campo]) : '';

        return preg_match('/^#[0-9a-fA-F]{6}$/', $valor) === 1 ? strtolower($valor) : null;
    }

    /**
     * Identificadores de lote vindos do formulario: so' inteiros positivos,
     * sem repeticao. Mesma limpeza do traco OperacaoEmLote, usada aqui para
     * recusar lista vazia ANTES de chamar o repositorio.
     */
    private function inteirosDoLote(array $ids)
    {
        $limpos = [];

        foreach ($ids as $id) {
            $numero = (int) $id;

            if ($numero > 0 && !in_array($numero, $limpos, true)) {
                $limpos[] = $numero;
            }
        }

        return $limpos;
    }

    private function avisarResultado(array $resultado)
    {
        if ($resultado['emitidos'] > 0) {
            $mensagem = $resultado['emitidos'] . ' certificado(s) emitido(s).';

            if (!empty($resultado['alcancou_teto'])) {
                $mensagem .= ' O teto de ' . CertificadoEmissaoService::TETO_LOTE
                    . ' por vez foi alcançado: repita a operação para continuar.';
            }

            flashSucesso($mensagem);
        } elseif ($resultado['repetidos'] > 0) {
            flashAlerta('Nada novo a emitir: os certificados escolhidos já estavam emitidos.');
        }

        if ($resultado['falhas'] !== []) {
            flashErro('Não foi possível emitir: ' . implode(' | ', array_slice($resultado['falhas'], 0, 3)));
        }
    }

    private function documentoFormatado(array $dossie)
    {
        $documento = (string) $dossie['documento'];

        if ($documento === '') {
            return '';
        }

        return (string) $dossie['tipo_documento'] === 'CPF' ? CpfValidador::formatar($documento) : $documento;
    }

    private function nomeDoArquivo(array $certificado)
    {
        return 'certificado-' . $certificado['codigo_verificacao'] . '.pdf';
    }
}
