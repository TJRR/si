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
use App\Repositories\HomologacaoPublicaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\AcessoParticipanteService;

class HomologacaoController extends Controller
{
    private $equipes;
    private $participantes;
    private $trilhas;
    private $usuarioParticipante;
    private $notificacoes;
    private $homologacaoPublica;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->equipes = new EquipeRepository();
        $this->participantes = new ParticipanteRepository();
        $this->trilhas = new TrilhaRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->homologacaoPublica = new HomologacaoPublicaRepository();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $status = isset($_GET['status']) ? $_GET['status'] : '';

        $this->renderizar('admin/homologacao/index', [
            'trilha' => $trilha,
            'inscricoes' => $this->equipes->listarTodosPorTrilha($trilhaId, $status),
            'statusFiltro' => $status,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
            'homologacaoPublicada' => $this->homologacaoPublica->jaPublicado($trilhaId),
        ], 'Inscritos — ' . $trilha['nome'], ['tipo' => 'inscritos', 'id' => (int) $trilhaId]);

        unset($_SESSION['flash']);
    }

    /**
     * Fase 19 (#17): publica/despublica a pagina publica de equipes
     * homologadas desta trilha (fora do fluxo de homologar/rejeitar
     * integrante, que qualquer Suporte ja pode fazer - publicar dado pra
     * fora e' decisao de Admin).
     */
    public function publicar($trilhaId)
    {
        RoleMiddleware::exigir(['administrador']);
        $this->homologacaoPublica->publicar($trilhaId, Auth::usuarioId());
        $_SESSION['flash'] = 'Lista de equipes homologadas publicada.';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function despublicar($trilhaId)
    {
        RoleMiddleware::exigir(['administrador']);
        $this->homologacaoPublica->reabrir($trilhaId);
        $_SESSION['flash'] = 'Lista de equipes homologadas despublicada.';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function homologar()
    {
        $vinculoId = (int) (isset($_POST['vinculo_id']) ? $_POST['vinculo_id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        $this->homologarUmVinculo($vinculoId, $trilhaId);
        $_SESSION['flash'] = 'Participante homologado e acesso liberado.';

        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function rejeitar()
    {
        $vinculoId = (int) (isset($_POST['vinculo_id']) ? $_POST['vinculo_id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);
        $motivo = trim(isset($_POST['motivo']) ? $_POST['motivo'] : '');

        $this->rejeitarUmVinculo($vinculoId, $motivo !== '' ? $motivo : null);
        $_SESSION['flash'] = 'Participante rejeitado.';

        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function homologarEmMassa()
    {
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);
        $vinculoIds = isset($_POST['vinculo_ids']) && is_array($_POST['vinculo_ids']) ? $_POST['vinculo_ids'] : [];

        foreach ($vinculoIds as $vinculoId) {
            $this->homologarUmVinculo((int) $vinculoId, $trilhaId);
        }

        $_SESSION['flash'] = count($vinculoIds) . ' inscrição(ões) homologada(s).';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function rejeitarEmMassa()
    {
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);
        $vinculoIds = isset($_POST['vinculo_ids']) && is_array($_POST['vinculo_ids']) ? $_POST['vinculo_ids'] : [];
        $motivo = trim(isset($_POST['motivo']) ? $_POST['motivo'] : '');

        foreach ($vinculoIds as $vinculoId) {
            $this->rejeitarUmVinculo((int) $vinculoId, $motivo !== '' ? $motivo : null);
        }

        $_SESSION['flash'] = count($vinculoIds) . ' inscrição(ões) rejeitada(s).';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    private function homologarUmVinculo($vinculoId, $trilhaId)
    {
        $vinculo = $this->equipes->buscarVinculoPorId($vinculoId);

        if ($vinculo === null) {
            return;
        }

        $participante = $this->participantes->buscarPorId($vinculo['participante_id']);
        $equipe = $this->equipes->buscarPorId($vinculo['equipe_id']);

        $this->equipes->homologarVinculo($vinculoId, Auth::usuarioId());
        $this->limparNotificacaoRejeicao($vinculo['participante_id']);
        (new AcessoParticipanteService())->liberarAcesso($participante, $trilhaId, $equipe['nome_equipe']);
    }

    private function rejeitarUmVinculo($vinculoId, $motivo)
    {
        $vinculo = $this->equipes->buscarVinculoPorId($vinculoId);

        if ($vinculo === null) {
            return;
        }

        $this->equipes->rejeitarVinculo($vinculoId, Auth::usuarioId(), $motivo);

        $equipe = $this->equipes->buscarPorId($vinculo['equipe_id']);
        $mensagem = 'Sua inscrição na equipe "' . $equipe['nome_equipe'] . '" foi rejeitada.'
            . ($motivo !== null ? ' Motivo: ' . $motivo : ' Nenhum motivo foi informado.');

        foreach ($this->usuarioParticipante->usuariosDoParticipante($vinculo['participante_id']) as $usuarioId) {
            $this->notificacoes->garantirUnica(
                $usuarioId,
                'equipe_rejeitada',
                'Inscrição rejeitada',
                $mensagem,
                ['url' => url('participante/index')]
            );
        }
    }

    private function limparNotificacaoRejeicao($participanteId)
    {
        foreach ($this->usuarioParticipante->usuariosDoParticipante($participanteId) as $usuarioId) {
            $this->notificacoes->removerPorTipo($usuarioId, 'equipe_rejeitada');
        }
    }
}
