<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\RegraDesempateRepository;
use App\Repositories\TrilhaRepository;

class RegraDesempateAdminController extends Controller
{
    private $regras;
    private $trilhas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->regras = new RegraDesempateRepository();
        $this->trilhas = new TrilhaRepository();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $this->renderizar('admin/desempate/index', [
            'trilha' => $trilha,
            'regras' => $this->regras->listarPorTrilha($trilhaId),
        ], 'Regras de desempate de ' . $trilha['nome']);
    }

    public function novo($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $criteriosDisponiveis = $this->regras->listarCriteriosDisponiveisPorTrilha($trilhaId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $criterioAvaliacaoId = (int) (isset($_POST['criterio_avaliacao_id']) ? $_POST['criterio_avaliacao_id'] : 0);
            $direcao = isset($_POST['direcao']) ? $_POST['direcao'] : 'desc';

            $criterioValido = false;
            foreach ($criteriosDisponiveis as $criterio) {
                if ((int) $criterio['id'] === $criterioAvaliacaoId) {
                    $criterioValido = true;
                    break;
                }
            }

            if (!$criterioValido) {
                $erro = 'Selecione um critério válido desta trilha.';
            } else {
                $this->regras->criar($trilhaId, $criterioAvaliacaoId, $direcao === 'asc' ? 'asc' : 'desc');
                $this->redirecionar('desempate/index/' . $trilhaId);
                return;
            }
        }

        $this->renderizar('admin/desempate/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'criteriosDisponiveis' => $criteriosDisponiveis,
        ], 'Novo critério de desempate');
    }

    public function mover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $direcao = isset($_POST['direcao']) ? $_POST['direcao'] : 'cima';
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        $this->regras->mover($id, $direcao);
        $this->redirecionar('desempate/index/' . $trilhaId);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        $this->regras->remover($id);
        $this->redirecionar('desempate/index/' . $trilhaId);
    }
}
