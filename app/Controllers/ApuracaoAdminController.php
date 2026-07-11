<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EtapaRepository;
use App\Repositories\FormulaPontuacaoRepository;
use App\Repositories\RegraDesempateRepository;
use App\Repositories\ResultadoTrilhaRepository;
use App\Repositories\TrilhaRepository;
use App\Services\ResultadoTrilhaService;

/**
 * Reune numa unica tela as 3 pecas que compoem a apuracao de uma trilha —
 * formula da nota final, regras de desempate e resultado final — sem duplicar
 * a logica de leitura/escrita, que continua em FormulaPontuacaoAdminController,
 * RegraDesempateAdminController e ResultadoAdminController.
 */
class ApuracaoAdminController extends Controller
{
    private $trilhas;
    private $etapas;
    private $formulas;
    private $regras;
    private $resultados;
    private $servicoResultado;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->formulas = new FormulaPontuacaoRepository();
        $this->regras = new RegraDesempateRepository();
        $this->resultados = new ResultadoTrilhaRepository();
        $this->servicoResultado = new ResultadoTrilhaService();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $formula = $this->formulas->buscarPorTrilha($trilhaId);
        $etapasDaTrilha = $this->etapas->listarPorTrilha($trilhaId);

        $erroResultado = null;
        $ranking = [];
        $publicado = $this->servicoResultado->jaPublicado($trilhaId);

        try {
            $ranking = $publicado
                ? $this->resultados->listarPorTrilha($trilhaId)
                : $this->servicoResultado->calcularRanking($trilhaId);
        } catch (\RuntimeException $e) {
            $erroResultado = $e->getMessage();
        }

        $this->renderizar('admin/apuracao/index', [
            'trilha' => $trilha,
            'expressaoAtual' => $formula !== null ? $formula['expressao'] : '',
            'etapasDaTrilha' => $etapasDaTrilha,
            'regras' => $this->regras->listarPorTrilha($trilhaId),
            'ranking' => $ranking,
            'publicado' => $publicado,
            'erroResultado' => $erroResultado,
        ], 'Apuração — ' . $trilha['nome'], ['tipo' => 'apuracao', 'id' => (int) $trilhaId]);
    }
}
