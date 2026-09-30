<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\BonusConfigRepository;
use App\Repositories\BonusCreditoRepository;
use App\Repositories\BonusRepository;
use App\Repositories\EventoAtividadeTipoRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\BonusApuracaoService;
use App\Validation\CpfValidador;

/**
 * Fase 57: Bonus (no da arvore irmao de Atividades, Trabalhos, Estandes,
 * Conexoes e Divulgacao). Suporte le, como nos demais modulos do Evento;
 * toda gravacao exige Administrador.
 *
 * Bonus e' ENTIDADE CADASTRAVEL: esta tela cria, edita e ordena os bonus do
 * evento, com nome, tipo, exigencia e pontos. O que o codigo sabe apurar
 * sao os TIPOS (BonusApuracaoService::TIPOS).
 *
 * A lista de creditos e' NOMINAL, como a de Divulgacao e ao contrario da de
 * Conexoes: e' dela que sai a entrega do premio de quem fechou o Bingo
 * (o projeto do evento preve mudas de plantas), e sem o nome nao ha' como
 * conferir um credito suspeito.
 */
class BonusAdminController extends Controller
{
    private $eventos;
    private $config;
    private $bonus;
    private $creditos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new BonusConfigRepository();
        $this->bonus = new BonusRepository();
        $this->creditos = new BonusCreditoRepository();
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

    private function bonusDoEventoOu404(array $evento, $bonusId)
    {
        $bonus = $this->bonus->buscarPorId($bonusId);

        if ($bonus === null || (int) $bonus['evento_id'] !== (int) $evento['id']) {
            http_response_code(404);
            exit('Bônus não encontrado neste evento.');
        }

        return $bonus;
    }

    /**
     * Catalogo do evento: e' a primeira aba porque o cadastro e' o que faz
     * o modulo existir. Sem bonus cadastrado, nao ha' o que apurar.
     */
    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $this->renderizar('admin/bonus/index', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'bonus' => $this->bonus->listarPorEvento($id),
            'tipos' => BonusApuracaoService::TIPOS,
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Bônus: ' . $evento['nome'], ['tipo' => 'bonus', 'id' => $id]);
    }

    public function novo($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            $erros = $this->validar($id, $dados, null);

            if ($erros === []) {
                $this->bonus->criar($id, $dados);
                $this->reapurar($evento, 'Bônus cadastrado.');
                $this->redirecionar('bonus/index/' . $id);
                return;
            }

            $this->renderizarFormulario($evento, $dados, $erros, null);
            return;
        }

        $this->renderizarFormulario($evento, $this->dadosVazios(), [], null);
    }

    public function editar($eventoId, $bonusId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];
        $bonus = $this->bonusDoEventoOu404($evento, $bonusId);
        $temCredito = $this->bonus->temCredito((int) $bonus['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            // O tipo nunca muda em bonus com credito: creditos de uma regra
            // passariam a valer como creditos de outra, com o mesmo nome.
            $dados['tipo'] = $bonus['tipo'];
            $erros = $this->validar($id, $dados, (int) $bonus['id']);

            if ($erros === []) {
                $this->bonus->atualizar((int) $bonus['id'], $dados);
                $this->reapurar($evento, 'Bônus atualizado. Os créditos já concedidos mantêm os pontos e a exigência daquele momento.');
                $this->redirecionar('bonus/index/' . $id);
                return;
            }

            $this->renderizarFormulario($evento, $dados, $erros, $bonus, $temCredito);
            return;
        }

        $dados = [
            'nome' => $bonus['nome'],
            'descricao' => $bonus['descricao'],
            'tipo' => $bonus['tipo'],
            'exigencia' => (int) $bonus['exigencia'],
            'tipo_atividade_id' => $bonus['tipo_atividade_id'] !== null ? (int) $bonus['tipo_atividade_id'] : null,
            'pontos' => (int) $bonus['pontos'],
            'ativo' => (int) $bonus['ativo'],
        ];

        $this->renderizarFormulario($evento, $dados, [], $bonus, $temCredito);
    }

    /**
     * "Remover" so' apaga de verdade o bonus que nunca creditou ninguem. Com
     * credito, o caminho e' desativar: some do painel do participante e da
     * apuracao, e o que ja' foi concedido continua valendo.
     */
    public function remover($eventoId, $bonusId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('bonus/index/' . $id);
            return;
        }

        $bonus = $this->bonusDoEventoOu404($evento, $bonusId);

        if ($this->bonus->temCredito((int) $bonus['id'])) {
            $this->bonus->atualizar((int) $bonus['id'], [
                'nome' => $bonus['nome'],
                'descricao' => $bonus['descricao'],
                'exigencia' => (int) $bonus['exigencia'],
                'tipo_atividade_id' => $bonus['tipo_atividade_id'] !== null ? (int) $bonus['tipo_atividade_id'] : null,
                'pontos' => (int) $bonus['pontos'],
                'ativo' => 0,
            ]);
            flashAlerta('Este bônus já concedeu pontos, então foi desativado em vez de removido: ele some do aplicativo e os créditos já dados continuam valendo.');
            $this->redirecionar('bonus/index/' . $id);
            return;
        }

        $this->bonus->remover((int) $bonus['id']);
        flashSucesso('Bônus removido.');
        $this->redirecionar('bonus/index/' . $id);
    }

    public function reordenar($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $corpo = json_decode(file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? $corpo['ids'] : [];

        $this->bonus->reordenar((int) $evento['id'], $ids);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    /**
     * Acompanhamento: numeros do evento e a lista nominal dos creditos.
     */
    public function acompanhamento($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $filtros = [
            'bonus_id' => isset($_GET['bonus_id']) ? (int) $_GET['bonus_id'] : 0,
            'situacao' => isset($_GET['situacao']) ? (string) $_GET['situacao'] : '',
        ];

        $this->renderizar('admin/bonus/acompanhamento', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'bonus' => $this->bonus->listarPorEvento($id),
            'numeros' => $this->creditos->numerosPorEvento($id),
            'creditos' => $this->creditos->listarDoEvento($id, $filtros),
            'filtros' => $filtros,
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Acompanhamento dos bônus: ' . $evento['nome'], ['tipo' => 'bonusAcompanhamento', 'id' => $id]);
    }

    /**
     * Reconferencia sob demanda. Existe para os desencontros que nao passam
     * por presenca nem por salvamento de cadastro, como a data de uma
     * atividade corrigida depois do evento.
     */
    public function reconferir($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $this->reapurar($evento, 'Bônus reconferidos.');
        $this->redirecionar('bonus/acompanhamento/' . $id);
    }

    /**
     * Relacao de quem fechou um bonus, em CSV. E' a lista do balcao de
     * entrega do premio, entao leva o documento, formatado por
     * CpfValidador::formatar() quando for CPF.
     */
    public function exportar($eventoId, $bonusId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $bonus = $this->bonusDoEventoOu404($evento, $bonusId);
        $linhas = $this->creditos->listarFechamentos((int) $bonus['id']);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bonus-' . (int) $bonus['id'] . '.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.
        fputcsv($saida, ['Nome', 'Documento', 'Correio eletrônico', 'Bônus', 'Exigência atingida', 'Pontos', 'Concedido em', 'Situação'], ';');

        foreach ($linhas as $linha) {
            fputcsv($saida, [
                $linha['participante_nome'],
                $this->documentoFormatado($linha),
                $linha['participante_email'],
                $bonus['nome'],
                (int) $linha['exigencia_atingida'],
                (int) $linha['pontos_creditados'],
                $linha['creditado_em'],
                $linha['anulado_em'] === null ? 'Válido' : 'Anulado',
            ], ';');
        }

        fclose($saida);
        exit;
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $this->config->salvar($id, ['ativo' => isset($_POST['ativo'])]);
            $this->reapurar($evento, 'Configurações dos bônus salvas.');
            $this->redirecionar('bonus/configuracoes/' . $id);
            return;
        }

        $this->renderizar('admin/bonus/configuracoes', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'totalBonus' => count($this->bonus->listarPorEvento($id)),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Configurações dos bônus: ' . $evento['nome'], ['tipo' => 'bonusConfiguracoes', 'id' => $id]);
    }

    /**
     * Anulacao com justificativa. Ao contrario da Fase 56, anular NAO
     * devolve a possibilidade de ganhar o bonus de novo: a condicao continua
     * cumprida para sempre (presenca nao some), entao a chave unica mantem a
     * linha no lugar e a apuracao a pula. O conserto de um engano e' a
     * reversao.
     */
    public function anular($eventoId, $creditoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $credito = $this->creditos->buscarPorId($creditoId);

        if ($credito === null || (int) $credito['evento_id'] !== $id) {
            flashErro('Crédito não encontrado neste evento.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $motivo = trim(isset($_POST['motivo']) ? (string) $_POST['motivo'] : '');

        if ($motivo === '') {
            flashErro('Informe o motivo da anulação: ele é mostrado ao participante.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $motivo = mb_substr($motivo, 0, 500);

        if (!$this->creditos->anular((int) $credito['id'], Auth::usuarioId(), $motivo)) {
            flashAlerta('Este crédito já estava anulado.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $credito, $motivo);

        flashSucesso('Crédito anulado e participante avisado. Ele não volta sozinho: só a reversão o restabelece.');
        $this->redirecionar('bonus/acompanhamento/' . $id);
    }

    public function reverterAnulacao($eventoId, $creditoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $credito = $this->creditos->buscarPorId($creditoId);

        if ($credito === null || (int) $credito['evento_id'] !== $id) {
            flashErro('Crédito não encontrado neste evento.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        if (!$this->creditos->reverterAnulacao((int) $credito['id'], Auth::usuarioId())) {
            flashAlerta('Este crédito não está anulado.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $credito, null);

        flashSucesso('Anulação desfeita: a pontuação voltou a valer.');
        $this->redirecionar('bonus/acompanhamento/' . $id);
    }

    /**
     * Fase 57 (bloco E, pendencia 35): cancela de uma vez todos os creditos
     * validos de um bonus. Existia so' a anulacao linha a linha, e um bonus
     * cadastrado errado que creditou o evento inteiro so' se consertava
     * assim, um por um.
     *
     * Dupla trava no molde de ModeloDocumentoAdminController::expurgar():
     * justificativa obrigatoria e o nome do bonus redigitado, conferido no
     * servidor. A trava de data daquele molde nao se aplica aqui.
     *
     * E' anulacao HUMANA (anulado_por preenchido), entao nao volta sozinha:
     * o caminho de volta e' a reversao em lote.
     */
    public function anularEmLote($eventoId, $bonusId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $bonus = $this->bonusDoEventoOu404($evento, $bonusId);
        $motivo = trim(isset($_POST['motivo']) ? (string) $_POST['motivo'] : '');

        if ($motivo === '') {
            flashErro('Informe o motivo do cancelamento: ele é mostrado a cada participante atingido.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $confirmacao = trim(isset($_POST['confirmacao']) ? (string) $_POST['confirmacao'] : '');

        if ($confirmacao !== $bonus['nome']) {
            flashErro('Confirmação incorreta: digite exatamente o nome do bônus para cancelar todos os créditos dele.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $atingidos = $this->creditos->anularEmLote((int) $bonus['id'], Auth::usuarioId(), mb_substr($motivo, 0, 500));

        if ($atingidos === []) {
            flashAlerta('Este bônus não tem crédito válido para cancelar.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $this->avisarEmLote($evento, $bonus, $atingidos, mb_substr($motivo, 0, 500));

        flashSucesso(count($atingidos) . ' créditos de "' . $bonus['nome'] . '" foram cancelados, e cada participante foi avisado.');
        $this->redirecionar('bonus/acompanhamento/' . $id);
    }

    /**
     * Desfaz o cancelamento em lote. Alcanca so' o que foi anulado por
     * pessoa: credito que o sistema anulou por falta de base continua como
     * esta', porque ele volta sozinho quando a condicao voltar.
     */
    public function reverterAnulacaoEmLote($eventoId, $bonusId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $bonus = $this->bonusDoEventoOu404($evento, $bonusId);
        $atingidos = $this->creditos->reverterAnulacaoEmLote((int) $bonus['id'], Auth::usuarioId());

        if ($atingidos === []) {
            flashAlerta('Este bônus não tem crédito anulado pelo Administrador para restabelecer.');
            $this->redirecionar('bonus/acompanhamento/' . $id);
            return;
        }

        $this->avisarEmLote($evento, $bonus, $atingidos, null);

        flashSucesso(count($atingidos) . ' créditos de "' . $bonus['nome'] . '" voltaram a valer, e cada participante foi avisado.');
        $this->redirecionar('bonus/acompanhamento/' . $id);
    }

    /**
     * Um aviso no sino por pessoa atingida. E' o mesmo laco que o envio em
     * massa do Evento ja' usa; com algumas centenas de creditos a requisicao
     * demora, e a tela avisa isso antes do clique.
     */
    private function avisarEmLote(array $evento, array $bonus, array $atingidos, $motivo)
    {
        $sino = new NotificacaoPainelRepository();

        foreach ($atingidos as $credito) {
            if (empty($credito['usuario_id'])) {
                continue;
            }

            if ($motivo !== null) {
                $titulo = 'Pontos de bônus removidos';
                $mensagem = 'O bônus "' . $bonus['nome'] . '" em ' . $evento['nome'] . ' foi cancelado pela organização. Motivo: ' . $motivo;
            } else {
                $titulo = 'Pontos de bônus restabelecidos';
                $mensagem = 'O cancelamento do bônus "' . $bonus['nome'] . '" em ' . $evento['nome'] . ' foi desfeito, e a pontuação voltou a valer.';
            }

            $sino->criar(
                (int) $credito['usuario_id'],
                'bonus_anulacao',
                $titulo,
                $mensagem,
                ['url' => url('eventoApp/index/' . (int) $evento['id'])]
            );
        }
    }

    private function dadosVazios()
    {
        return [
            'nome' => '',
            'descricao' => '',
            'tipo' => 'atividades_distintas',
            'exigencia' => 0,
            'tipo_atividade_id' => null,
            'pontos' => 0,
            'ativo' => 1,
        ];
    }

    private function dadosDoFormulario()
    {
        $tipo = isset($_POST['tipo']) ? (string) $_POST['tipo'] : '';

        if (!isset(BonusApuracaoService::TIPOS[$tipo])) {
            $tipo = 'atividades_distintas';
        }

        $pedeNumero = BonusApuracaoService::TIPOS[$tipo]['pede_numero'];
        $pedeTipoAtividade = BonusApuracaoService::TIPOS[$tipo]['pede_tipo_atividade'];
        $tipoAtividadeId = isset($_POST['tipo_atividade_id']) ? (int) $_POST['tipo_atividade_id'] : 0;

        return [
            'nome' => mb_substr(trim(isset($_POST['nome']) ? (string) $_POST['nome'] : ''), 0, 150),
            'descricao' => $this->textoOuNulo(isset($_POST['descricao']) ? $_POST['descricao'] : null, 300),
            'tipo' => $tipo,
            'exigencia' => $pedeNumero ? max(0, (int) (isset($_POST['exigencia']) ? $_POST['exigencia'] : 0)) : 0,
            'tipo_atividade_id' => $pedeTipoAtividade && $tipoAtividadeId > 0 ? $tipoAtividadeId : null,
            'pontos' => max(0, (int) (isset($_POST['pontos']) ? $_POST['pontos'] : 0)),
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    private function validar($eventoId, array $dados, $bonusId)
    {
        $erros = [];
        $regra = BonusApuracaoService::TIPOS[$dados['tipo']];

        if ($dados['nome'] === '') {
            $erros['nome'] = 'Informe o nome do bônus, que é o que o participante vê.';
        }

        if ($regra['pede_numero'] && $dados['exigencia'] < 1) {
            $erros['exigencia'] = 'Informe quantas o bônus exige: com zero, ninguém o ganharia.';
        }

        if ($regra['pede_tipo_atividade'] && $dados['tipo_atividade_id'] === null) {
            $erros['tipo_atividade_id'] = 'Escolha o tipo de atividade que este bônus considera.';
        }

        if ($erros === [] && $this->bonus->existeDuplicata($eventoId, $dados['tipo'], $dados['exigencia'], $dados['tipo_atividade_id'], $bonusId)) {
            $erros['tipo'] = 'Já existe um bônus deste tipo com a mesma exigência neste evento.';
        }

        return $erros;
    }

    private function renderizarFormulario(array $evento, array $dados, array $erros, $bonus, $temCredito = false)
    {
        $id = (int) $evento['id'];

        $this->renderizar('admin/bonus/form', [
            'evento' => $evento,
            'bonus' => $bonus,
            'dados' => $dados,
            'erros' => $erros,
            'temCredito' => $temCredito,
            'tipos' => BonusApuracaoService::TIPOS,
            'tiposAtividade' => (new EventoAtividadeTipoRepository())->listar($id),
            'atividadesSemTipo' => $this->bonus->atividadesSemTipo($id),
        ], ($bonus === null ? 'Novo bônus: ' : 'Editar bônus: ') . $evento['nome'], ['tipo' => 'bonus', 'id' => $id]);
    }

    /**
     * Reapuracao aditiva depois de cada alteracao no cadastro: baixar a
     * exigencia credita na hora quem ja' cumpria, e subir a exigencia nao
     * tira nada de ninguem.
     */
    private function reapurar(array $evento, $mensagem)
    {
        $novos = (new BonusApuracaoService())->apurarEvento($evento);

        if ($novos > 0) {
            $mensagem .= ' ' . $novos . ($novos === 1 ? ' crédito novo foi concedido' : ' créditos novos foram concedidos') . ' na reconferência.';
        }

        flashSucesso($mensagem);
    }

    private function avisarParticipante(array $evento, array $credito, $motivo)
    {
        $inscricao = (new EventoInscricaoRepository())->buscarPorId($credito['evento_inscricao_id']);

        if ($inscricao === null || empty($inscricao['usuario_id'])) {
            return;
        }

        if ($motivo !== null) {
            $titulo = 'Pontos de bônus removidos';
            $mensagem = 'Um bônus seu em ' . $evento['nome'] . ' foi anulado pela organização. Motivo: ' . $motivo;
        } else {
            $titulo = 'Pontos de bônus restabelecidos';
            $mensagem = 'A anulação de um bônus seu em ' . $evento['nome'] . ' foi desfeita, e a pontuação voltou a valer.';
        }

        (new NotificacaoPainelRepository())->criar(
            (int) $inscricao['usuario_id'],
            'bonus_anulacao',
            $titulo,
            $mensagem,
            ['url' => url('eventoApp/index/' . (int) $evento['id'])]
        );
    }

    private function documentoFormatado(array $linha)
    {
        $documento = isset($linha['participante_documento']) ? (string) $linha['participante_documento'] : '';

        if ($documento === '') {
            return '';
        }

        $tipo = isset($linha['participante_tipo_documento']) ? (string) $linha['participante_tipo_documento'] : '';

        return strtoupper($tipo) === 'CPF' ? CpfValidador::formatar($documento) : $documento;
    }

    private function textoOuNulo($valor, $limite)
    {
        $valor = is_string($valor) ? trim($valor) : '';

        return $valor !== '' ? mb_substr($valor, 0, $limite) : null;
    }
}
