<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Middleware\RoleMiddleware;
use App\Repositories\CompeticaoParticipacaoRepository;
use App\Repositories\CompeticaoRepository;
use App\Repositories\EventoAtividadeRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\GamificacaoService;

/**
 * Fase 58: no "Competicoes" do Evento (Karaoke, Batalha de Prompts). A
 * dinamica de pontos v2 pontua a PARTICIPACAO, lida num codigo "na mao do
 * responsavel", sem inscricao previa; vencer nao pontua, entao nao ha'
 * resultado nem lancamento de vencedor.
 *
 * O codigo e' mostrado de dois jeitos (decisao do dono): cartao impresso,
 * daqui, e tela cheia para o facilitador da atividade ligada, em "Minhas
 * facilitacoes" no aplicativo.
 *
 * Suporte le; toda gravacao exige Administrador. Depois do encerramento da
 * gincana, nenhuma anulacao ou reversao move pontos.
 */
class CompeticaoAdminController extends Controller
{
    private $eventos;
    private $competicoes;
    private $participacoes;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->competicoes = new CompeticaoRepository();
        $this->participacoes = new CompeticaoParticipacaoRepository();
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

    private function competicaoDoEventoOu404(array $evento, $competicaoId)
    {
        $competicao = $this->competicoes->buscarPorId((int) $competicaoId);

        if ($competicao === null || (int) $competicao['evento_id'] !== (int) $evento['id']) {
            http_response_code(404);
            exit('Competição não encontrada neste evento.');
        }

        return $competicao;
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $this->renderizar('admin/competicoes/index', [
            'evento' => $evento,
            'competicoes' => $this->competicoes->listarPorEvento($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Competições: ' . $evento['nome'], ['tipo' => 'competicoes', 'id' => $id]);
    }

    public function novo($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($id, $dados);

            if ($erro === null) {
                $this->competicoes->criar($id, $dados);
                flashSucesso('Competição cadastrada. O código de participação já pode ser impresso.');
                $this->redirecionar('competicoes/index/' . $id);
                return;
            }

            flashErro($erro);
            $this->renderizarFormulario($evento, $dados, null);
            return;
        }

        $this->renderizarFormulario($evento, $this->dadosVazios(), null);
    }

    public function editar($eventoId, $competicaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $competicao = $this->competicaoDoEventoOu404($evento, $competicaoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($id, $dados);

            if ($erro === null) {
                $this->competicoes->atualizar((int) $competicao['id'], $dados);
                flashSucesso('Competição atualizada. As participações já registradas mantêm os pontos daquele momento.');
                $this->redirecionar('competicoes/index/' . $id);
                return;
            }

            flashErro($erro);
            $this->renderizarFormulario($evento, $dados, $competicao);
            return;
        }

        $this->renderizarFormulario($evento, [
            'nome' => $competicao['nome'],
            'regras_html' => (string) $competicao['regras_html'],
            'atividade_id' => $competicao['atividade_id'] !== null ? (int) $competicao['atividade_id'] : null,
            'pontos_participacao' => (int) $competicao['pontos_participacao'],
            'ativo' => (int) $competicao['ativo'],
        ], $competicao);
    }

    /**
     * So' apaga de verdade a competicao sem participacao nenhuma; com
     * participacao, desativa (as participacoes e os pontos continuam).
     */
    public function remover($eventoId, $competicaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('competicoes/index/' . $id);
            return;
        }

        $competicao = $this->competicaoDoEventoOu404($evento, $competicaoId);

        if ($this->competicoes->temParticipacao((int) $competicao['id'])) {
            $this->competicoes->desativar((int) $competicao['id']);
            flashAlerta('Esta competição já tem participações, então foi desativada em vez de removida: o código deixa de valer e os pontos já dados continuam.');
            $this->redirecionar('competicoes/index/' . $id);
            return;
        }

        $this->competicoes->remover((int) $competicao['id']);
        flashSucesso('Competição removida.');
        $this->redirecionar('competicoes/index/' . $id);
    }

    public function reordenar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->competicoes->reordenar((int) $evento['id'], $ids);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    /**
     * Cartao do codigo de participacao, para o responsavel levar na mao.
     * Pagina solta, sem layout.php, no molde do cartaz de atividade.
     */
    public function cartao($eventoId, $competicaoId = null)
    {
        $evento = $this->eventoOu404($eventoId);
        $competicao = $this->competicaoDoEventoOu404($evento, $competicaoId);

        echo View::renderizarString('admin/gamificacao/cartaz_codigo', [
            'evento' => $evento,
            'titulo' => $competicao['nome'],
            'subtitulo' => 'Participação',
            'codigo' => $competicao['codigo_participacao'],
            'instrucao' => 'Quem cantar ou competir abre o aplicativo do evento, toca em "Ler código" e aponta a câmera para este código.',
        ]);
    }

    /**
     * Participacoes registradas, com filtro por competicao e situacao.
     */
    public function participacoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $filtros = $this->filtrosDaRequisicao();

        $this->renderizar('admin/competicoes/participacoes', [
            'evento' => $evento,
            'competicoes' => $this->competicoes->listarPorEvento($id),
            'participacoes' => $this->participacoes->listarDoEvento($id, $filtros),
            'filtros' => $filtros,
            'gincanaEncerrada' => GamificacaoService::encerrada($id),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Participações em competições: ' . $evento['nome'], ['tipo' => 'competicoesParticipacoes', 'id' => $id]);
    }

    /**
     * Anulacao com motivo, que a pessoa le. Serve, por exemplo, para quem
     * leu o codigo de uma foto sem ter participado. Anular nao reabre a
     * vaga: a chave unica continua valendo.
     */
    public function anular($eventoId, $participacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $this->recusarSeEncerrada($id)) {
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        $participacao = $this->participacoes->buscarPorId((int) $participacaoId);

        if ($participacao === null || (int) $participacao['evento_id'] !== $id) {
            flashErro('Participação não encontrada neste evento.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        $motivo = trim(isset($_POST['motivo']) ? (string) $_POST['motivo'] : '');

        if ($motivo === '') {
            flashErro('Informe o motivo da anulação: ele é mostrado ao participante.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        $motivo = mb_substr($motivo, 0, 500);

        if (!$this->participacoes->anular((int) $participacao['id'], Auth::usuarioId(), $motivo)) {
            flashAlerta('Esta participação já estava anulada.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $participacao, $motivo);

        flashSucesso('Participação anulada e participante avisado.');
        $this->redirecionar('competicoes/participacoes/' . $id);
    }

    public function reverterAnulacao($eventoId, $participacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $this->recusarSeEncerrada($id)) {
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        $participacao = $this->participacoes->buscarPorId((int) $participacaoId);

        if ($participacao === null || (int) $participacao['evento_id'] !== $id) {
            flashErro('Participação não encontrada neste evento.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        if (!$this->participacoes->reverterAnulacao((int) $participacao['id'], Auth::usuarioId())) {
            flashAlerta('Esta participação não está anulada.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $participacao, null);

        flashSucesso('Anulação desfeita: a pontuação voltou a valer.');
        $this->redirecionar('competicoes/participacoes/' . $id);
    }

    /**
     * Participacoes em planilha de texto, no molde da exportacao de Bonus.
     */
    public function exportar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $linhas = $this->participacoes->listarDoEvento((int) $evento['id'], $this->filtrosDaRequisicao());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="participacoes-competicoes-evento-' . (int) $evento['id'] . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.
        fputcsv($saida, ['Competição', 'Nome', 'Correio eletrônico', 'Pontos', 'Participou em', 'Situação'], ';');

        foreach ($linhas as $linha) {
            fputcsv($saida, [
                $linha['competicao_nome'],
                $linha['participante_nome'],
                $linha['participante_email'],
                (int) $linha['pontos_creditados'],
                $linha['participou_em'],
                $linha['anulado_em'] === null ? 'Válida' : 'Anulada',
            ], ';');
        }

        fclose($saida);
        exit;
    }

    private function recusarSeEncerrada($eventoId)
    {
        if (!GamificacaoService::encerrada($eventoId)) {
            return false;
        }

        flashErro('A gincana deste evento foi encerrada e a classificação está congelada: nenhuma pontuação pode ser alterada.');

        return true;
    }

    /**
     * Filtros da tela: competicao, situacao, busca por pessoa e periodo.
     * Convencao do projeto: campo em branco vira '' e nao entra na consulta.
     */
    private function filtrosDaRequisicao()
    {
        return [
            'competicao_id' => isset($_GET['competicao_id']) ? (int) $_GET['competicao_id'] : 0,
            'situacao' => isset($_GET['situacao']) ? trim((string) $_GET['situacao']) : '',
            'busca' => isset($_GET['busca']) ? trim((string) $_GET['busca']) : '',
            'data_inicio' => isset($_GET['data_inicio']) ? trim((string) $_GET['data_inicio']) : '',
            'data_fim' => isset($_GET['data_fim']) ? trim((string) $_GET['data_fim']) : '',
        ];
    }

    /**
     * Fase 58: anulacao e reversao em lote das participacoes marcadas.
     * Recusa lista vazia em vez de "executar" sobre nada.
     */
    public function anularEmLote($eventoId)
    {
        $evento = $this->prepararLote($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $motivo = trim(isset($_POST['motivo']) ? (string) $_POST['motivo'] : '');

        if ($motivo === '') {
            flashErro('Informe o motivo da anulação: ele é mostrado a cada participante atingido.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        $atingidas = $this->participacoes->anularEmLote($id, $this->identificadoresDoLote(), Auth::usuarioId(), mb_substr($motivo, 0, 500));

        if ($atingidas === []) {
            flashAlerta('Nenhuma das participações marcadas podia ser anulada.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        foreach ($atingidas as $participacao) {
            $this->avisarParticipante($evento, $participacao, $motivo);
        }

        flashSucesso(count($atingidas) . ' participação(ões) anulada(s), e os participantes atingidos foram avisados.');
        $this->redirecionar('competicoes/participacoes/' . $id);
    }

    public function reverterAnulacaoEmLote($eventoId)
    {
        $evento = $this->prepararLote($eventoId);

        if ($evento === null) {
            return;
        }

        $id = (int) $evento['id'];
        $atingidas = $this->participacoes->reverterAnulacaoEmLote($id, $this->identificadoresDoLote());

        if ($atingidas === []) {
            flashAlerta('Nenhuma das participações marcadas estava anulada.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return;
        }

        foreach ($atingidas as $participacao) {
            $this->avisarParticipante($evento, $participacao, null);
        }

        flashSucesso(count($atingidas) . ' anulação(ões) desfeita(s): a pontuação voltou a valer.');
        $this->redirecionar('competicoes/participacoes/' . $id);
    }

    private function prepararLote($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('competicoes/participacoes/' . $id);
            return null;
        }

        if (GamificacaoService::encerrada($id)) {
            flashErro('A gincana deste evento foi encerrada e a classificação está congelada: nenhuma pontuação pode ser alterada.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return null;
        }

        if ($this->identificadoresDoLote() === []) {
            flashAlerta('Selecione ao menos uma participação.');
            $this->redirecionar('competicoes/participacoes/' . $id);
            return null;
        }

        return $evento;
    }

    private function identificadoresDoLote()
    {
        return isset($_POST['participacao_ids']) && is_array($_POST['participacao_ids']) ? $_POST['participacao_ids'] : [];
    }

    private function avisarParticipante(array $evento, array $participacao, $motivo)
    {
        if (empty($participacao['usuario_id'])) {
            return;
        }

        if ($motivo !== null) {
            $titulo = 'Pontos de competição removidos';
            $mensagem = 'A sua participação em "' . $participacao['competicao_nome'] . '", em ' . $evento['nome']
                . ', foi anulada pela organização. Motivo: ' . $motivo;
        } else {
            $titulo = 'Pontos de competição restabelecidos';
            $mensagem = 'A anulação da sua participação em "' . $participacao['competicao_nome'] . '", em ' . $evento['nome']
                . ', foi desfeita, e a pontuação voltou a valer.';
        }

        (new NotificacaoPainelRepository())->criar(
            (int) $participacao['usuario_id'],
            'competicao_anulacao',
            $titulo,
            $mensagem,
            ['url' => url('eventoApp/index/' . (int) $evento['id'])]
        );
    }

    private function dadosVazios()
    {
        return [
            'nome' => '',
            'regras_html' => '',
            'atividade_id' => null,
            'pontos_participacao' => 0,
            'ativo' => 1,
        ];
    }

    private function dadosDoFormulario()
    {
        $atividadeId = isset($_POST['atividade_id']) ? (int) $_POST['atividade_id'] : 0;

        return [
            'nome' => mb_substr(trim(isset($_POST['nome']) ? (string) $_POST['nome'] : ''), 0, 150),
            'regras_html' => isset($_POST['regras_html']) && trim((string) $_POST['regras_html']) !== ''
                ? sanitizarHtmlRico($_POST['regras_html'])
                : '',
            'atividade_id' => $atividadeId > 0 ? $atividadeId : null,
            'pontos_participacao' => max(0, min(65535, (int) (isset($_POST['pontos_participacao']) ? $_POST['pontos_participacao'] : 0))),
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    /**
     * A atividade ligada precisa ser do mesmo evento: e' ela que da' a
     * janela de leitura e quem e' o facilitador que ve o codigo.
     */
    private function validar($eventoId, array $dados)
    {
        if ($dados['nome'] === '') {
            return 'Informe o nome da competição, que é o que o participante vê.';
        }

        if ($dados['atividade_id'] !== null) {
            $atividade = (new EventoAtividadeRepository())->buscarPorId($dados['atividade_id']);

            if ($atividade === null || (int) $atividade['evento_id'] !== (int) $eventoId) {
                return 'Escolha uma atividade deste evento.';
            }
        }

        return null;
    }

    private function renderizarFormulario(array $evento, array $dados, $competicao)
    {
        $id = (int) $evento['id'];

        $this->renderizar('admin/competicoes/form', [
            'evento' => $evento,
            'dados' => $dados,
            'competicao' => $competicao,
            'atividades' => (new EventoAtividadeRepository())->listarPorEvento($id),
        ], ($competicao === null ? 'Nova competição: ' : 'Editar competição: ') . $evento['nome'], ['tipo' => 'competicoes', 'id' => $id]);
    }
}
