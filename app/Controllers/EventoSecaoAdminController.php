<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoSecaoCartoesRepository;
use App\Repositories\EventoSecaoContagemRepository;
use App\Repositories\EventoSecaoCronogramaRepository;
use App\Repositories\EventoSecaoDestaquesRepository;
use App\Repositories\EventoSecaoFaqRepository;
use App\Repositories\EventoSecaoLocalRepository;
use App\Repositories\EventoSecaoOrdemRepository;
use App\Repositories\EventoSecaoProgramacaoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoEixoTematicoRepository;
use App\Services\ImagemService;

/**
 * Fase 51: "Seções da página" do Evento. Duas responsabilidades:
 *
 * 1. A ordem da pagina publica inteira (quadros, faixas, cada bloco e cada
 *    componente), com liga/desliga e o que entra no menu do cabecalho.
 * 2. O cadastro dos componentes em si (contagem regressiva, cronograma,
 *    cartoes, destaques, programacao, perguntas frequentes, local), cada um
 *    com tabela e tela propria, varias instancias por evento se o Admin
 *    quiser.
 *
 * O tipo do componente entra como parametro de rota, e cada tipo tem sua
 * view; o que e' comum (criar instancia, itens, ordem dos itens) vem de
 * EventoSecaoRepositorioBase, sem duplicar CRUD sete vezes.
 */
class EventoSecaoAdminController extends Controller
{
    /**
     * Registro dos componentes: rotulo na interface, repositorio, pasta da
     * view e se tem lista de itens. Tipo fora daqui nao existe para o
     * controller (nenhuma rota generica aceita nome arbitrario de tabela).
     */
    private static $componentes = [
        'contagem' => ['rotulo' => 'Contagem regressiva', 'classe' => EventoSecaoContagemRepository::class, 'view' => 'evento_contagem', 'tem_itens' => true],
        'cronograma' => ['rotulo' => 'Cronograma', 'classe' => EventoSecaoCronogramaRepository::class, 'view' => 'evento_cronograma', 'tem_itens' => true],
        'cartoes' => ['rotulo' => 'Cartões', 'classe' => EventoSecaoCartoesRepository::class, 'view' => 'evento_cartoes', 'tem_itens' => true],
        'destaques' => ['rotulo' => 'Destaques', 'classe' => EventoSecaoDestaquesRepository::class, 'view' => 'evento_destaques', 'tem_itens' => true],
        'programacao' => ['rotulo' => 'Programação', 'classe' => EventoSecaoProgramacaoRepository::class, 'view' => 'evento_programacao', 'tem_itens' => true],
        'faq' => ['rotulo' => 'Perguntas frequentes', 'classe' => EventoSecaoFaqRepository::class, 'view' => 'evento_faq', 'tem_itens' => true],
        'local' => ['rotulo' => 'Local e acesso', 'classe' => EventoSecaoLocalRepository::class, 'view' => 'evento_local', 'tem_itens' => false],
    ];

    private static $rotulosFixos = [
        'quadros' => 'Quadros de apresentação',
        'faixas' => 'Faixas',
        'bloco' => 'Bloco de conteúdo',
    ];

    private $eventos;
    private $ordem;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->ordem = new EventoSecaoOrdemRepository();
    }

    public static function componentes()
    {
        return self::$componentes;
    }

    public static function rotuloDoTipo($tipo)
    {
        if (isset(self::$componentes[$tipo])) {
            return self::$componentes[$tipo]['rotulo'];
        }

        return isset(self::$rotulosFixos[$tipo]) ? self::$rotulosFixos[$tipo] : $tipo;
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

    private function componenteOu404($tipo)
    {
        if (!isset(self::$componentes[$tipo])) {
            http_response_code(404);
            exit('Tipo de seção não encontrado.');
        }

        return self::$componentes[$tipo];
    }

    private function repositorioDe($tipo)
    {
        $componente = $this->componenteOu404($tipo);
        $classe = $componente['classe'];

        return new $classe();
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $this->ordem->garantirSecoesFixas($eventoId);

        $this->renderizar('admin/evento_secoes/index', [
            'evento' => $evento,
            'secoes' => $this->ordem->listarOrdenado($eventoId),
            'componentes' => self::$componentes,
            'rotulosFixos' => self::$rotulosFixos,
        ], 'Seções da página: ' . $evento['nome'], ['tipo' => 'eventoSecoes', 'id' => (int) $eventoId]);
    }

    public function reordenar($eventoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->ordem->reordenar($eventoId, $ids);

        echo json_encode(['ok' => true]);
    }

    /**
     * Liga/desliga e menu de uma secao, direto da lista.
     */
    public function salvarItem($eventoId)
    {
        $secaoId = (int) (isset($_POST['secao_id']) ? $_POST['secao_id'] : 0);

        $this->ordem->atualizarItem($eventoId, $secaoId, [
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
            'mostrar_no_menu' => isset($_POST['mostrar_no_menu']) ? 1 : 0,
            'rotulo_menu' => isset($_POST['rotulo_menu']) ? $_POST['rotulo_menu'] : null,
        ]);

        flashSucesso('Seção atualizada.');
        $this->redirecionar('eventoSecoes/index/' . $eventoId);
    }

    /**
     * Cria uma instancia vazia do componente e abre o formulario dela: o
     * Admin nunca precisa preencher nada antes de a secao existir na
     * pagina, e a linha de ordem nasce junto, no fim da lista.
     */
    public function adicionar($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $componente = $this->componenteOu404($tipo);
        $repositorio = $this->repositorioDe($tipo);

        $dados = [];

        foreach ($this->colunasDoFormulario($tipo) as $coluna) {
            $dados[$coluna] = $this->valorPadraoDaColuna($tipo, $coluna);
        }

        $secaoId = $repositorio->criar($eventoId, $dados);
        $this->ordem->registrarSecao($eventoId, $tipo, $secaoId, $componente['rotulo']);

        flashSucesso($componente['rotulo'] . ' adicionado à página. Preencha o conteúdo abaixo.');
        $this->redirecionar('eventoSecoes/editar/' . $eventoId . '/' . $tipo . '/' . $secaoId);
    }

    public function editar($eventoId, $tipo = null, $secaoId = null)
    {
        $evento = $this->eventoOu404($eventoId);
        $componente = $this->componenteOu404($tipo);
        $repositorio = $this->repositorioDe($tipo);
        $secao = $repositorio->buscarDoEvento($eventoId, (int) $secaoId);

        if ($secao === null) {
            http_response_code(404);
            exit('Seção não encontrada.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $repositorio->atualizar($eventoId, (int) $secaoId, $this->dadosDoFormulario($tipo, $secao));
            flashSucesso('Seção salva.');
            $this->redirecionar('eventoSecoes/editar/' . $eventoId . '/' . $tipo . '/' . (int) $secaoId);
            return;
        }

        $dadosView = [
            'evento' => $evento,
            'tipo' => $tipo,
            'rotuloTipo' => $componente['rotulo'],
            'secao' => $secao,
            'itens' => $componente['tem_itens'] ? $repositorio->listarItens((int) $secaoId) : [],
        ];

        if ($tipo === 'cartoes') {
            $dadosView['eixos'] = (new TrabalhoEixoTematicoRepository())->listarPorEvento($eventoId);
        }

        if ($tipo === 'destaques' || $tipo === 'programacao') {
            $dadosView['atividades'] = (new EventoSecaoProgramacaoRepository())->listarAtividadesDoEvento($eventoId);
        }

        $this->renderizar('admin/evento_secoes/' . $componente['view'], $dadosView, $componente['rotulo'] . ': ' . $evento['nome'], ['tipo' => 'eventoSecoes', 'id' => (int) $eventoId]);
    }

    public function remover($eventoId, $tipo = null, $secaoId = null)
    {
        $this->eventoOu404($eventoId);
        $this->componenteOu404($tipo);
        $repositorio = $this->repositorioDe($tipo);

        $secaoAlvo = $repositorio->buscarDoEvento($eventoId, (int) $secaoId);

        if ($secaoAlvo !== null) {
            $repositorio->remover($eventoId, (int) $secaoId, $tipo);
            flashSucesso('Seção removida da página.');
        }

        $this->redirecionar('eventoSecoes/index/' . $eventoId);
    }

    public function itemNovo($eventoId, $tipo = null, $secaoId = null)
    {
        $this->eventoOu404($eventoId);
        $this->componenteOu404($tipo);
        $repositorio = $this->repositorioDe($tipo);

        $repositorio->criarItem($eventoId, (int) $secaoId, $this->dadosDoItem($tipo));
        flashSucesso('Item adicionado.');
        $this->redirecionar('eventoSecoes/editar/' . $eventoId . '/' . $tipo . '/' . (int) $secaoId);
    }

    public function itemSalvar($eventoId, $tipo = null, $secaoId = null)
    {
        $this->eventoOu404($eventoId);
        $this->componenteOu404($tipo);
        $repositorio = $this->repositorioDe($tipo);
        $itemId = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);

        $repositorio->atualizarItem($eventoId, $itemId, $this->dadosDoItem($tipo));
        flashSucesso('Item salvo.');
        $this->redirecionar('eventoSecoes/editar/' . $eventoId . '/' . $tipo . '/' . (int) $secaoId);
    }

    public function itemRemover($eventoId, $tipo = null, $secaoId = null)
    {
        $this->eventoOu404($eventoId);
        $this->componenteOu404($tipo);
        $repositorio = $this->repositorioDe($tipo);
        $itemId = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);

        $repositorio->removerItem($eventoId, $itemId);
        flashSucesso('Item removido.');
        $this->redirecionar('eventoSecoes/editar/' . $eventoId . '/' . $tipo . '/' . (int) $secaoId);
    }

    /**
     * Colunas graváveis de cada componente, na mesma ordem que o
     * repositorio espera. Fica aqui, e nao no repositorio, porque e' o que
     * o formulario envia: o repositorio so' sabe gravar.
     */
    private function colunasDoFormulario($tipo)
    {
        $comuns = ['etiqueta', 'titulo', 'descricao_html', 'cor_fundo', 'cor_texto'];

        switch ($tipo) {
            case 'contagem':
                return array_merge($comuns, ['data_alvo', 'cor_circulo']);
            case 'cartoes':
                return array_merge($comuns, ['colunas', 'efeito_hover', 'efeito_abrir', 'efeito_fechar']);
            case 'destaques':
                return array_merge($comuns, ['fonte', 'colunas']);
            case 'programacao':
                return array_merge($comuns, ['fonte', 'mostrar_local']);
            case 'local':
                return array_merge($comuns, ['endereco', 'mapa_embed_url', 'mapa_link', 'imagem_path', 'imagem_alt']);
            default:
                return $comuns;
        }
    }

    private function valorPadraoDaColuna($tipo, $coluna)
    {
        $padroes = [
            'colunas' => 4,
            'fonte' => 'atividades',
            'mostrar_local' => 1,
            'efeito_hover' => 'elevar',
            'efeito_abrir' => 'deslizar',
            'efeito_fechar' => 'deslizar',
            'titulo' => self::rotuloDoTipo($tipo),
        ];

        return isset($padroes[$coluna]) ? $padroes[$coluna] : null;
    }

    private function dadosDoFormulario($tipo, array $secaoAtual)
    {
        $dados = [];

        foreach ($this->colunasDoFormulario($tipo) as $coluna) {
            $dados[$coluna] = $this->valorEnviado($coluna, $secaoAtual);
        }

        if ($tipo === 'local') {
            $dados['mapa_embed_url'] = $this->validarEnderecoDeMapa($dados['mapa_embed_url']);
            $dados['imagem_path'] = $this->resolverImagem($secaoAtual);
        }

        return $dados;
    }

    /**
     * Texto rico passa pelo mesmo saneamento das demais telas do painel
     * (sanitizarHtmlRico), sem excecao: e' texto que vai aparecer numa
     * pagina publica.
     */
    private function valorEnviado($coluna, array $atual)
    {
        $bruto = isset($_POST[$coluna]) ? $_POST[$coluna] : null;

        if (substr($coluna, -5) === '_html') {
            return $bruto !== null ? sanitizarHtmlRico($bruto) : null;
        }

        if ($coluna === 'mostrar_local') {
            return isset($_POST['mostrar_local']) ? 1 : 0;
        }

        if ($coluna === 'colunas') {
            $valor = (int) $bruto;

            return $valor >= 1 && $valor <= 6 ? $valor : 4;
        }

        if ($coluna === 'data_alvo') {
            return !empty($bruto) ? str_replace('T', ' ', $bruto) . (strlen($bruto) === 16 ? ':00' : '') : null;
        }

        if ($coluna === 'imagem_path') {
            return isset($atual['imagem_path']) ? $atual['imagem_path'] : null;
        }

        $valor = is_string($bruto) ? trim($bruto) : $bruto;

        return ($valor === '' || $valor === null) ? null : $valor;
    }

    /**
     * Mapa incorporado carrega conteudo de terceiros na pagina publica, o
     * que so foi aceito para esta secao: por isso o endereco e' conferido
     * no servidor contra uma lista curta de origens, nunca aceito como
     * digitado.
     */
    private function validarEnderecoDeMapa($endereco)
    {
        if (empty($endereco)) {
            return null;
        }

        $partes = parse_url($endereco);

        if ($partes === false || !isset($partes['scheme'], $partes['host']) || $partes['scheme'] !== 'https') {
            flashAlerta('O endereço do mapa foi ignorado: informe um endereço seguro do Google Maps ou do OpenStreetMap.');

            return null;
        }

        $origensAceitas = ['www.google.com', 'google.com', 'maps.google.com', 'www.openstreetmap.org', 'openstreetmap.org'];

        if (!in_array(strtolower($partes['host']), $origensAceitas, true)) {
            flashAlerta('O endereço do mapa foi ignorado: só são aceitos endereços do Google Maps ou do OpenStreetMap.');

            return null;
        }

        return $endereco;
    }

    private function resolverImagem(array $secaoAtual)
    {
        $atual = isset($secaoAtual['imagem_path']) ? $secaoAtual['imagem_path'] : null;

        if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] === UPLOAD_ERR_NO_FILE) {
            return $atual;
        }

        try {
            $novo = (new ImagemService())->salvar($_FILES['imagem'], 'evento-secoes', 1200, 1200);

            if ($atual !== null) {
                (new ImagemService())->remover($atual);
            }

            return $novo;
        } catch (\RuntimeException $e) {
            flashAlerta('A imagem não foi salva: ' . $e->getMessage());

            return $atual;
        }
    }

    private function dadosDoItem($tipo)
    {
        switch ($tipo) {
            case 'contagem':
                return [
                    'texto' => trim((string) $_POST['texto']),
                    'data_referencia' => !empty($_POST['data_referencia']) ? $_POST['data_referencia'] : null,
                    'cor_marcador' => !empty($_POST['cor_marcador']) ? $_POST['cor_marcador'] : null,
                ];
            case 'cronograma':
                return [
                    'periodo_texto' => trim((string) $_POST['periodo_texto']),
                    'descricao' => trim((string) $_POST['descricao']),
                    'data_referencia' => !empty($_POST['data_referencia']) ? $_POST['data_referencia'] : null,
                    'cor' => !empty($_POST['cor']) ? $_POST['cor'] : null,
                ];
            case 'cartoes':
                return [
                    'eixo_tematico_id' => !empty($_POST['eixo_tematico_id']) ? (int) $_POST['eixo_tematico_id'] : null,
                    'etiqueta' => !empty($_POST['etiqueta']) ? trim($_POST['etiqueta']) : null,
                    'titulo' => !empty($_POST['titulo']) ? trim($_POST['titulo']) : null,
                    'resumo' => !empty($_POST['resumo']) ? trim($_POST['resumo']) : null,
                    'detalhe_html' => isset($_POST['detalhe_html']) ? sanitizarHtmlRico($_POST['detalhe_html']) : null,
                    'cor' => !empty($_POST['cor']) ? $_POST['cor'] : null,
                ];
            case 'destaques':
                return [
                    'atividade_id' => !empty($_POST['atividade_id']) ? (int) $_POST['atividade_id'] : null,
                    'titulo' => !empty($_POST['titulo']) ? trim($_POST['titulo']) : null,
                    'quando_texto' => !empty($_POST['quando_texto']) ? trim($_POST['quando_texto']) : null,
                    'local' => !empty($_POST['local']) ? trim($_POST['local']) : null,
                    'descricao' => !empty($_POST['descricao']) ? trim($_POST['descricao']) : null,
                    'icone' => !empty($_POST['icone']) ? trim($_POST['icone']) : null,
                ];
            case 'programacao':
                return [
                    'atividade_id' => !empty($_POST['atividade_id']) ? (int) $_POST['atividade_id'] : null,
                    'dia' => !empty($_POST['dia']) ? $_POST['dia'] : null,
                    'turno' => in_array(isset($_POST['turno']) ? $_POST['turno'] : '', ['manha', 'tarde', 'noite'], true) ? $_POST['turno'] : 'manha',
                    'horario_texto' => !empty($_POST['horario_texto']) ? trim($_POST['horario_texto']) : null,
                    'tipo_texto' => !empty($_POST['tipo_texto']) ? trim($_POST['tipo_texto']) : null,
                    'titulo' => !empty($_POST['titulo']) ? trim($_POST['titulo']) : null,
                    'local' => !empty($_POST['local']) ? trim($_POST['local']) : null,
                    'descricao' => !empty($_POST['descricao']) ? trim($_POST['descricao']) : null,
                ];
            case 'faq':
                return [
                    'pergunta' => trim((string) $_POST['pergunta']),
                    'resposta_html' => isset($_POST['resposta_html']) ? sanitizarHtmlRico($_POST['resposta_html']) : '',
                    'ativo' => isset($_POST['ativo']) ? 1 : 0,
                ];
            default:
                return [];
        }
    }

    public function itemReordenar($eventoId, $tipo = null, $secaoId = null)
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->componenteOu404($tipo);
        $repositorio = $this->repositorioDe($tipo);

        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $repositorio->reordenarItens($eventoId, (int) $secaoId, $ids);

        echo json_encode(['ok' => true]);
    }
}
