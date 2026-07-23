<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EtapaRepository;
use App\Repositories\RegraDesempateRepository;
use App\Repositories\TrilhaRepository;

class RegraDesempateAdminController extends Controller
{
    public const TIPOS = ['criterio', 'data_submissao'];

    private $regras;
    private $trilhas;
    private $etapas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->regras = new RegraDesempateRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
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
            'etapas' => $this->etapas->listarPorTrilha($trilhaId),
            'regras' => $this->regras->listarPorTrilha($trilhaId),
        ], 'Regras de desempate de ' . $trilha['nome']);
    }

    public function novo($trilhaId, $etapaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($trilha === null || $etapa === null || (int) $etapa['trilha_id'] !== (int) $trilhaId) {
            http_response_code(404);
            exit('Trilha ou etapa não encontrada.');
        }

        $criteriosDisponiveis = $this->regras->listarCriteriosDisponiveisPorEtapa($etapaId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'criterio';
            $tipo = in_array($tipo, self::TIPOS, true) ? $tipo : 'criterio';
            $direcao = isset($_POST['direcao']) && $_POST['direcao'] === 'asc' ? 'asc' : 'desc';
            $criterioAvaliacaoId = null;

            if ($tipo === 'criterio') {
                $criterioAvaliacaoId = (int) (isset($_POST['criterio_avaliacao_id']) ? $_POST['criterio_avaliacao_id'] : 0);
                $criterioValido = false;

                foreach ($criteriosDisponiveis as $criterio) {
                    if ((int) $criterio['id'] === $criterioAvaliacaoId) {
                        $criterioValido = true;
                        break;
                    }
                }

                if (!$criterioValido) {
                    $erro = 'Selecione um critério válido desta etapa.';
                }
            }

            if ($erro === null) {
                $this->regras->criar($trilhaId, $etapaId, $tipo, $criterioAvaliacaoId, $direcao);
                $this->redirecionar('apuracao/index/' . $trilhaId);
                return;
            }
        }

        $this->renderizar('admin/desempate/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'etapa' => $etapa,
            'criteriosDisponiveis' => $criteriosDisponiveis,
        ], 'Novo critério de desempate', ['tipo' => 'apuracao', 'id' => (int) $trilhaId]);
    }

    public function mover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $direcao = isset($_POST['direcao']) ? $_POST['direcao'] : 'cima';
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        $this->regras->mover($id, $direcao);
        $this->redirecionar('apuracao/index/' . $trilhaId);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        $this->regras->remover($id);
        $this->redirecionar('apuracao/index/' . $trilhaId);
    }
}
