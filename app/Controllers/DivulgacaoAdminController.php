<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\DivulgacaoComprovacaoRepository;
use App\Repositories\DivulgacaoConfigRepository;
use App\Repositories\EventoInscricaoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\UsuarioPerfilRepository;
use App\Services\ArquivoPrivadoService;

/**
 * Fase 56: Divulgacao (no da arvore irmao de Atividades, Trabalhos,
 * Estandes e Conexoes). Suporte le, como nos demais modulos do Evento; toda
 * gravacao exige Administrador.
 *
 * Duas diferencas em relacao a Conexoes, as duas deliberadas:
 *
 * 1. A lista aqui e' NOMINAL. Em Conexoes o par e' dado pessoal de terceiro
 *    e por isso a tela administrativa e' so' agregada; aqui a conferencia
 *    por amostragem prevista no documento da dinamica de pontos depende de
 *    comparar a prova com a conta que a pessoa cadastrou em "Meu Perfil",
 *    o que exige o nome ao lado da prova.
 *
 * 2. A imagem de comprovacao e' servida SO' ao Administrador. E' o unico
 *    lugar desta fase com dado pessoal de terceiro que nao escolheu estar
 *    ali (quem aparece na captura de tela), e a analise e' atribuicao do
 *    Administrador. Suporte ve lista, numeros, endereco e a marca de prova
 *    repetida, e nao abre imagem.
 */
class DivulgacaoAdminController extends Controller
{
    private $eventos;
    private $comprovacoes;
    private $config;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->comprovacoes = new DivulgacaoComprovacaoRepository();
        $this->config = new DivulgacaoConfigRepository();
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

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $filtros = [
            'rede' => isset($_GET['rede']) ? (string) $_GET['rede'] : '',
            'tipo_acao' => isset($_GET['tipo_acao']) ? (string) $_GET['tipo_acao'] : '',
            'situacao' => isset($_GET['situacao']) ? (string) $_GET['situacao'] : '',
        ];

        $this->renderizar('admin/divulgacao/index', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'redes' => $this->config->listarRedes($id),
            'numeros' => $this->comprovacoes->numerosPorEvento($id),
            'porRedeETipo' => $this->comprovacoes->contarPorRedeETipo($id),
            'porDia' => $this->comprovacoes->contarPorDia($id),
            'comprovacoes' => $this->comprovacoes->listarPorEvento($id, $filtros),
            'resumosRepetidos' => $this->comprovacoes->resumosRepetidosNoEvento($id),
            'filtros' => $filtros,
            'rotulosRede' => UsuarioPerfilRepository::REDES_ROTULOS,
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Divulgação: ' . $evento['nome'], ['tipo' => 'divulgacao', 'id' => $id]);
    }

    /**
     * Imagem de comprovacao, so' para o Administrador. Suporte, que entra no
     * construtor, para aqui.
     */
    public function imagem($eventoId, $comprovacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $comprovacao = $this->comprovacoes->buscarPorId($comprovacaoId);

        if ($comprovacao === null || (int) $comprovacao['evento_id'] !== (int) $evento['id']) {
            http_response_code(404);
            exit('Comprovação não encontrada.');
        }

        if (empty($comprovacao['arquivo_path'])) {
            http_response_code(404);
            exit('Esta imagem foi apagada conforme a política de retenção de dados do evento.');
        }

        ArquivoPrivadoService::servirImagem($comprovacao['arquivo_path'], $comprovacao['arquivo_nome']);
    }

    /**
     * Anula a pontuacao de uma comprovacao, com justificativa obrigatoria.
     *
     * A linha nao e' apagada: ela sai de toda soma e de todo contador de
     * teto, o que devolve a vaga a pessoa e, no caso de "acompanhar",
     * devolve a possibilidade de registrar de novo. O que nao volta e' a
     * prova, que continua barrada pelos resumos gravados.
     */
    public function anular($eventoId, $comprovacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/index/' . $id);
            return;
        }

        $comprovacao = $this->comprovacoes->buscarPorId($comprovacaoId);

        if ($comprovacao === null || (int) $comprovacao['evento_id'] !== $id) {
            flashErro('Comprovação não encontrada neste evento.');
            $this->redirecionar('divulgacao/index/' . $id);
            return;
        }

        $motivo = trim(isset($_POST['motivo']) ? (string) $_POST['motivo'] : '');

        if ($motivo === '') {
            flashErro('Informe o motivo da anulação: ele é mostrado ao participante.');
            $this->redirecionar('divulgacao/index/' . $id);
            return;
        }

        $motivo = mb_substr($motivo, 0, 500);

        if (!$this->comprovacoes->anular((int) $comprovacao['id'], Auth::usuarioId(), $motivo)) {
            flashAlerta('Esta comprovação já estava anulada.');
            $this->redirecionar('divulgacao/index/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $comprovacao, $motivo);

        flashSucesso('Pontuação anulada e participante avisado.');
        $this->redirecionar('divulgacao/index/' . $id);
    }

    /**
     * Desfaz uma anulacao feita por engano. Sem este caminho, o unico
     * conserto seria pelo banco.
     */
    public function reverterAnulacao($eventoId, $comprovacaoId = null)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/index/' . $id);
            return;
        }

        $comprovacao = $this->comprovacoes->buscarPorId($comprovacaoId);

        if ($comprovacao === null || (int) $comprovacao['evento_id'] !== $id) {
            flashErro('Comprovação não encontrada neste evento.');
            $this->redirecionar('divulgacao/index/' . $id);
            return;
        }

        if (!$this->comprovacoes->reverterAnulacao((int) $comprovacao['id'], Auth::usuarioId())) {
            flashAlerta('Esta comprovação não está anulada.');
            $this->redirecionar('divulgacao/index/' . $id);
            return;
        }

        $this->avisarParticipante($evento, $comprovacao, null);

        flashSucesso('Anulação desfeita: a pontuação voltou a valer.');
        $this->redirecionar('divulgacao/index/' . $id);
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $this->config->salvar($id, [
                'ativo' => !empty($_POST['ativo']) ? 1 : 0,
                'data_inicio' => isset($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
                'data_fim' => isset($_POST['data_fim']) ? $_POST['data_fim'] : null,
            ]);

            $redes = isset($_POST['redes']) && is_array($_POST['redes']) ? $_POST['redes'] : [];
            $this->config->salvarRedes($id, $redes);

            flashSucesso('Configurações de Divulgação salvas.');
            $this->redirecionar('divulgacao/configuracoes/' . $id);
            return;
        }

        $this->renderizar('admin/divulgacao/configuracoes', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'redes' => $this->config->listarRedes($id),
            'totalImagens' => $this->contarImagens($id),
            'eventoEncerrado' => $this->eventoEncerrado($evento),
            'podeEditar' => Auth::possuiPerfil('administrador'),
        ], 'Configurações de Divulgação: ' . $evento['nome'], ['tipo' => 'divulgacaoConfiguracoes', 'id' => $id]);
    }

    /**
     * Apaga as imagens de comprovacao do evento. Irreversivel, por isso as
     * duas travas do molde de ModeloDocumentoAdminController::expurgar(): o
     * evento precisa ja ter passado da propria data final, e o Administrador
     * precisa redigitar o nome do evento.
     *
     * As linhas, os pontos e os resumos criptograficos permanecem: so' o
     * arquivo some, e a trava de prova repetida continua de pe'.
     */
    public function expurgarImagens($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('divulgacao/configuracoes/' . $id);
            return;
        }

        if (!$this->eventoEncerrado($evento)) {
            flashErro('Só é possível apagar as imagens de um evento que já passou da própria data final.');
            $this->redirecionar('divulgacao/configuracoes/' . $id);
            return;
        }

        $confirmacao = trim(isset($_POST['confirmacao']) ? (string) $_POST['confirmacao'] : '');

        if ($confirmacao !== $evento['nome']) {
            flashErro('Confirmação incorreta: digite exatamente o nome do evento para apagar as imagens.');
            $this->redirecionar('divulgacao/configuracoes/' . $id);
            return;
        }

        $apagadas = 0;

        foreach ($this->comprovacoes->listarComArquivoDoEvento($id) as $linha) {
            ArquivoPrivadoService::remover($linha['arquivo_path']);
            $this->comprovacoes->marcarArquivoRemovido($linha['id']);
            $apagadas++;
        }

        flashSucesso($apagadas . ' imagem(ns) apagada(s). As comprovações e os pontos continuam registrados.');
        $this->redirecionar('divulgacao/configuracoes/' . $id);
    }

    private function eventoEncerrado(array $evento)
    {
        if (empty($evento['data_fim'])) {
            return false;
        }

        return strtotime(substr((string) $evento['data_fim'], 0, 10) . ' 23:59:59') < time();
    }

    private function contarImagens($eventoId)
    {
        return count($this->comprovacoes->listarComArquivoDoEvento($eventoId));
    }

    /**
     * Aviso no sino da pessoa. Nao ha mensagem por correio eletronico nesta
     * fase (pendencia 28 de SGSI/pendencias.md).
     */
    private function avisarParticipante(array $evento, array $comprovacao, $motivo)
    {
        $inscricao = (new EventoInscricaoRepository())->buscarPorId($comprovacao['evento_inscricao_id']);

        if ($inscricao === null || empty($inscricao['usuario_id'])) {
            return;
        }

        if ($motivo !== null) {
            $titulo = 'Pontos de divulgação removidos';
            $mensagem = 'Uma comprovação sua em ' . $evento['nome'] . ' foi anulada pela organização. Motivo: ' . $motivo;
        } else {
            $titulo = 'Pontos de divulgação restabelecidos';
            $mensagem = 'A anulação de uma comprovação sua em ' . $evento['nome'] . ' foi desfeita, e a pontuação voltou a valer.';
        }

        (new NotificacaoPainelRepository())->criar(
            (int) $inscricao['usuario_id'],
            'divulgacao_anulacao',
            $titulo,
            $mensagem,
            ['url' => url('eventoApp/divulgacao/' . (int) $evento['id'])]
        );
    }
}
