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
use App\Repositories\ParticipanteRepository;
use App\Repositories\TrilhaRepository;
use App\Services\AcessoParticipanteService;

class HomologacaoController extends Controller
{
    private $equipes;
    private $participantes;
    private $trilhas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->equipes = new EquipeRepository();
        $this->participantes = new ParticipanteRepository();
        $this->trilhas = new TrilhaRepository();
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
        ], 'Inscritos — ' . $trilha['nome'], ['tipo' => 'inscritos', 'id' => (int) $trilhaId]);

        unset($_SESSION['flash']);
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

        $this->equipes->rejeitarVinculo($vinculoId, Auth::usuarioId(), $motivo !== '' ? $motivo : null);
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
            $this->equipes->rejeitarVinculo((int) $vinculoId, Auth::usuarioId(), $motivo !== '' ? $motivo : null);
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
        (new AcessoParticipanteService())->liberarAcesso($participante, $trilhaId, $equipe['nome_equipe']);
    }
}
