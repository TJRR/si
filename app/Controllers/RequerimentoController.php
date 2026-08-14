<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\ModeloDocumentoRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\RequerimentoRepository;
use App\Repositories\RequerimentoRespostaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\AcessoEtapaService;
use App\Services\ArquivoPrivadoService;
use App\Services\ModeloDocumentoService;
use App\Services\PdfService;
use App\Validation\UploadPdfValidador;

/**
 * Fase 30: canal do lider da equipe pra escolher um modelo de documento,
 * gerar o PDF sem assinatura, assinar fora do sistema (gov.br) e devolver o
 * PDF assinado - a partir dai' o pedido entra no fluxo de analise (ver
 * RequerimentoAdminController). So' o lider da equipe (papel = 'lider' em
 * equipe_participante) tem acesso - diferente de Duvida, que qualquer
 * integrante pode abrir, aqui a assinatura tem peso juridico individual.
 */
class RequerimentoController extends Controller
{
    const LIMITE_UPLOAD_BYTES = 15728640; // 15 MB, mesmo limite de SubmissaoService.

    private $usuarioParticipante;
    private $participantes;
    private $equipes;
    private $etapas;
    private $trilhas;
    private $modelos;
    private $requerimentos;
    private $respostas;
    private $acessoEtapa;
    private $notificacoes;
    private $perfis;
    private $modeloDocumentoService;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->participantes = new ParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->modelos = new ModeloDocumentoRepository();
        $this->requerimentos = new RequerimentoRepository();
        $this->respostas = new RequerimentoRespostaRepository();
        $this->acessoEtapa = new AcessoEtapaService();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
        $this->modeloDocumentoService = new ModeloDocumentoService();
    }

    public function index()
    {
        $equipe = $this->equipeDoLiderAtual();
        $trilha = $this->trilhas->buscarPorId($equipe['trilha_id']);

        $this->renderizar('participante/requerimentos', [
            'modelosDisponiveis' => $this->modelosDisponiveis($equipe, $trilha),
            'requerimentos' => $this->requerimentos->listarPorEquipe($equipe['id']),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Requerimentos');

        unset($_SESSION['flash']);
    }

    public function novo($modeloDocumentoId)
    {
        $equipe = $this->equipeDoLiderAtual();
        $modelo = $this->modeloDisponivelOuAbortar($modeloDocumentoId, $equipe);

        $emAndamento = $this->requerimentos->buscarEmAndamentoPorEquipeEModelo($equipe['id'], $modeloDocumentoId);

        if ($emAndamento !== null) {
            $this->redirecionar('requerimento/ver/' . $emAndamento['id']);
            return;
        }

        $this->renderizar('participante/requerimento_novo', [
            'modelo' => $modelo,
        ], 'Novo requerimento — ' . $modelo['nome']);
    }

    public function gerarPdf($modeloDocumentoId)
    {
        $equipe = $this->equipeDoLiderAtual();
        $modelo = $this->modeloDisponivelOuAbortar($modeloDocumentoId, $equipe);

        if ($this->requerimentos->buscarEmAndamentoPorEquipeEModelo($equipe['id'], $modeloDocumentoId) !== null) {
            $_SESSION['flash'] = 'Já existe um pedido em andamento para este modelo.';
            $this->redirecionar('requerimento/index');
            return;
        }

        $necessidade = trim(isset($_POST['necessidade']) ? $_POST['necessidade'] : '');

        if ($necessidade === '') {
            $_SESSION['flash'] = 'Descreva a necessidade antes de gerar o documento.';
            $this->redirecionar('requerimento/novo/' . $modeloDocumentoId);
            return;
        }

        $etapa = $this->etapas->buscarPorId($modelo['etapa_id']);
        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        $liderId = $this->participanteAtual()['id'];

        $requerimentoId = $this->requerimentos->criar(
            $modeloDocumentoId,
            $etapa['id'],
            $trilha['id'],
            $trilha['concurso_id'],
            $equipe['id'],
            $liderId,
            $necessidade
        );

        $this->streamarPdfSemAssinatura($requerimentoId);
    }

    public function gerarPdfNovamente($id)
    {
        $equipe = $this->equipeDoLiderAtual();
        $requerimento = $this->requerimentoDaEquipeAtual($id, $equipe);

        if ($requerimento['status'] !== 'aguardando_assinatura') {
            http_response_code(403);
            exit('Este pedido não está aguardando geração de documento.');
        }

        $necessidade = trim(isset($_POST['necessidade']) ? $_POST['necessidade'] : '');

        if ($necessidade === '') {
            $_SESSION['flash'] = 'Descreva a necessidade antes de gerar o documento.';
            $this->redirecionar('requerimento/ver/' . $id);
            return;
        }

        $this->requerimentos->atualizarNecessidade($id, $necessidade);
        $this->streamarPdfSemAssinatura($id);
    }

    /**
     * So' permitida enquanto nada foi protocolado ainda - exclusao
     * definitiva do rascunho, ver RequerimentoRepository::descartar().
     */
    public function descartar($id)
    {
        $equipe = $this->equipeDoLiderAtual();
        $requerimento = $this->requerimentoDaEquipeAtual($id, $equipe);

        if ($requerimento['status'] !== 'aguardando_assinatura' || $requerimento['enviado_em'] !== null) {
            http_response_code(403);
            exit('Só é possível descartar um rascunho ainda não enviado.');
        }

        $this->requerimentos->descartar($id);

        $_SESSION['flash'] = 'Rascunho descartado.';
        $this->redirecionar('requerimento/index');
    }

    public function enviarAssinado($id)
    {
        $equipe = $this->equipeDoLiderAtual();
        $requerimento = $this->requerimentoDaEquipeAtual($id, $equipe);

        if ($requerimento['status'] !== 'aguardando_assinatura') {
            http_response_code(403);
            exit('Este pedido não está aguardando o documento assinado.');
        }

        if (empty($_FILES['pdf_assinado']) || $_FILES['pdf_assinado']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['flash'] = 'Selecione o PDF assinado antes de enviar.';
            $this->redirecionar('requerimento/ver/' . $id);
            return;
        }

        $validacao = UploadPdfValidador::validar($_FILES['pdf_assinado'], self::LIMITE_UPLOAD_BYTES);

        if (!$validacao['valido']) {
            $_SESSION['flash'] = $validacao['mensagem'];
            $this->redirecionar('requerimento/ver/' . $id);
            return;
        }

        if (!$this->contemAssinaturaDigital($_FILES['pdf_assinado']['tmp_name'])) {
            $_SESSION['flash'] = 'Este PDF não contém nenhuma assinatura digital — assine no gov.br antes de enviar.';
            $this->redirecionar('requerimento/ver/' . $id);
            return;
        }

        try {
            $caminho = ArquivoPrivadoService::salvar($_FILES['pdf_assinado'], 'requerimentos/' . (int) $id);
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
            $this->redirecionar('requerimento/ver/' . $id);
            return;
        }

        $this->requerimentos->marcarEnviado($id, $caminho, $_FILES['pdf_assinado']['name']);
        $this->notificarAdministradoresNovoRequerimento($this->requerimentos->buscarPorId($id));

        $_SESSION['flash'] = 'Documento assinado enviado. Seu pedido entrou para análise.';
        $this->redirecionar('requerimento/ver/' . $id);
    }

    public function ver($id)
    {
        $equipe = $this->equipeDoLiderAtual();
        $requerimento = $this->requerimentoDaEquipeAtual($id, $equipe);

        $this->renderizar('participante/requerimento_ver', [
            'requerimento' => $requerimento,
            'respostas' => $this->respostas->listarPorRequerimento($id),
            'limiteMB' => round(self::LIMITE_UPLOAD_BYTES / 1024 / 1024, 1),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Requerimento');

        unset($_SESSION['flash']);
    }

    public function baixarAssinado($id)
    {
        $equipe = $this->equipeDoLiderAtual();
        $requerimento = $this->requerimentoDaEquipeAtual($id, $equipe);

        if ($requerimento['expurgado_em'] !== null) {
            http_response_code(404);
            exit('Este documento foi expurgado em ' . formatarData($requerimento['expurgado_em']) . ', conforme a política de retenção de dados.');
        }

        if ($requerimento['pdf_assinado_path'] === null) {
            http_response_code(404);
            exit('Nenhum documento assinado enviado ainda.');
        }

        ArquivoPrivadoService::servir($requerimento['pdf_assinado_path'], $requerimento['pdf_assinado_nome_original']);
    }

    /**
     * O lider tambem precisa poder baixar um anexo que veio numa resposta
     * do Administrador (ex.: PDF de credenciais, no desfecho "aprovado") -
     * nao so' o proprio PDF assinado que ele enviou.
     */
    public function baixarAnexoResposta($id, $respostaId)
    {
        $equipe = $this->equipeDoLiderAtual();
        $requerimento = $this->requerimentoDaEquipeAtual($id, $equipe);
        $resposta = $this->respostas->buscarPorId($respostaId);

        if ($resposta === null || (int) $resposta['requerimento_id'] !== (int) $requerimento['id']) {
            http_response_code(404);
            exit('Resposta não encontrada.');
        }

        if ($resposta['anexo_expurgado_em'] !== null) {
            http_response_code(404);
            exit('Este documento foi expurgado em ' . formatarData($resposta['anexo_expurgado_em']) . ', conforme a política de retenção de dados.');
        }

        if ($resposta['anexo_path'] === null) {
            http_response_code(404);
            exit('Esta resposta não tem anexo.');
        }

        ArquivoPrivadoService::servir($resposta['anexo_path'], $resposta['anexo_nome_original']);
    }

    private function streamarPdfSemAssinatura($requerimentoId)
    {
        $requerimento = $this->requerimentos->buscarPorId($requerimentoId);
        $modelo = $this->modelos->buscarPorId($requerimento['modelo_documento_id']);
        $equipe = $this->equipes->buscarPorId($requerimento['equipe_id']);
        $trilha = $this->trilhas->buscarPorId($requerimento['trilha_id']);
        $lider = $this->participantes->buscarPorId($requerimento['participante_id']);

        $dados = $this->modeloDocumentoService->montarDados($equipe, $lider, $trilha, $requerimento['necessidade']);
        $corpoResolvido = $this->modeloDocumentoService->resolver($modelo['corpo_html'], $dados);

        PdfService::streamar(
            'pdf/requerimento',
            ['corpoHtml' => $corpoResolvido],
            'requerimento-' . $requerimentoId . '.pdf',
            'portrait',
            'A4',
            true
        );
    }

    /**
     * Checagem estrutural (nao criptografica) - so' confirma que o PDF
     * carrega ALGUM bloco de assinatura digital embutido (marcador
     * /SubFilter com adbe.pkcs7.detached ou ETSI.CAdES.detached, os dois
     * padroes que um PDF assinado no gov.br carrega), pra pegar o erro mais
     * comum (reenviar o PDF original sem assinar). Nao confirma
     * autenticidade nem de quem e' a assinatura - a validacao completa
     * (cadeia ICP-Brasil) ficou fora desta fase por decisao registrada no
     * plano ("Conferência da assinatura e quem decide").
     */
    private function contemAssinaturaDigital($caminhoArquivo)
    {
        $conteudo = file_get_contents($caminhoArquivo);

        if ($conteudo === false) {
            return false;
        }

        return preg_match('/\/SubFilter\s*\/(adbe\.pkcs7\.detached|ETSI\.CAdES\.detached)/', $conteudo) === 1;
    }

    private function modelosDisponiveis(array $equipe, array $trilha)
    {
        $disponiveis = [];

        foreach ($this->modelos->listarAtivosPorTrilha($trilha['id']) as $modelo) {
            $etapaDoModelo = $this->etapas->buscarPorId($modelo['etapa_id']);

            if ($this->acessoEtapa->motivoBloqueio($etapaDoModelo, $equipe['id']) === null) {
                $disponiveis[] = $modelo;
            }
        }

        return $disponiveis;
    }

    private function modeloDisponivelOuAbortar($modeloDocumentoId, array $equipe)
    {
        $modelo = $this->modelos->buscarPorId($modeloDocumentoId);

        if ($modelo === null || !$modelo['ativo']) {
            http_response_code(404);
            exit('Modelo não encontrado.');
        }

        $etapa = $this->etapas->buscarPorId($modelo['etapa_id']);

        if ((int) $etapa['trilha_id'] !== (int) $equipe['trilha_id']) {
            http_response_code(403);
            exit('Acesso negado: este modelo não pertence à trilha da sua equipe.');
        }

        if ($this->acessoEtapa->motivoBloqueio($etapa, $equipe['id']) !== null) {
            http_response_code(403);
            exit('Acesso negado: sua equipe ainda não está apta para este modelo.');
        }

        return $modelo;
    }

    private function notificarAdministradoresNovoRequerimento(array $requerimento)
    {
        foreach ($this->perfis->listarUsuariosPorPerfilConcurso('administrador', $requerimento['concurso_id']) as $admin) {
            $this->notificacoes->criar(
                (int) $admin['id'],
                'requerimento_novo',
                'Novo requerimento protocolado',
                '"' . $requerimento['participante_nome'] . '" (equipe "' . $requerimento['nome_equipe'] . '") enviou o documento assinado de "' . $requerimento['modelo_nome'] . '".',
                ['url' => url('requerimentoAdmin/ver/' . (int) $requerimento['id'])]
            );
        }
    }

    private function participanteAtual()
    {
        $participantes = $this->usuarioParticipante->participantesDoUsuario(Auth::usuarioId());

        return !empty($participantes) ? $participantes[0] : null;
    }

    /**
     * Copiado de ParticipanteController::equipeDoLiderAtual() (metodo
     * privado la', nao reaproveitavel entre classes) - mesmo padrao ja
     * usado por DuvidaController::equipeAtual() pra resolver a equipe do
     * usuario logado.
     */
    private function equipeDoLiderAtual()
    {
        $participante = $this->participanteAtual();

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $equipe = $this->equipes->buscarPorParticipante($participante['id']);

        if ($equipe === null) {
            http_response_code(404);
            exit('Nenhuma equipe encontrada para este participante.');
        }

        $vinculo = $this->equipes->buscarVinculo($equipe['id'], $participante['id']);

        if ($vinculo === null || $vinculo['papel'] !== 'lider') {
            http_response_code(403);
            exit('Acesso negado: apenas o líder da equipe pode gerenciar requerimentos.');
        }

        return $equipe;
    }

    private function requerimentoDaEquipeAtual($id, array $equipe)
    {
        $requerimento = $this->requerimentos->buscarPorId($id);

        if ($requerimento === null || (int) $requerimento['equipe_id'] !== (int) $equipe['id']) {
            http_response_code(404);
            exit('Requerimento não encontrado.');
        }

        return $requerimento;
    }
}
