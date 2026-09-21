<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Core\ExpressaoAritmetica;
use App\Middleware\RoleMiddleware;
use App\Repositories\CriterioAvaliacaoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormulaPontuacaoRepository;
use App\Repositories\TrilhaRepository;

class FormulaPontuacaoAdminController extends Controller
{
    private $formulas;
    private $etapas;
    private $trilhas;
    private $criterios;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->formulas = new FormulaPontuacaoRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->criterios = new CriterioAvaliacaoRepository();
    }

    public function etapa($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $criteriosDaEtapa = $this->criterios->listarPorEtapa($etapaId);
        $variaveisPermitidas = array_column($criteriosDaEtapa, 'codigo');

        $erro = null;
        $resultadoTeste = null;
        $expressaoAtual = null;
        $casasDecimaisAtual = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $expressaoAtual = trim(isset($_POST['expressao']) ? $_POST['expressao'] : '');
            $casasDecimaisAtual = isset($_POST['casas_decimais']) ? (int) $_POST['casas_decimais'] : 2;
            $acao = isset($_POST['acao']) ? $_POST['acao'] : 'salvar';

            if ($acao === 'testar') {
                $resultadoTeste = $this->testar($expressaoAtual, $variaveisPermitidas);
            } else {
                $validacao = ExpressaoAritmetica::validar($expressaoAtual, $variaveisPermitidas);

                if (!$validacao['valido']) {
                    $erro = $validacao['mensagem'];
                } elseif ($casasDecimaisAtual < 0 || $casasDecimaisAtual > 4) {
                    $erro = 'Casas decimais precisa ser um número entre 0 e 4.';
                } else {
                    $this->formulas->salvarParaEtapa($etapaId, $expressaoAtual, $casasDecimaisAtual);
                    $this->redirecionar('formulas/etapa/' . (int) $etapaId);
                    return;
                }
            }
        }

        $formula = $this->formulas->buscarPorEtapa($etapaId);

        if ($expressaoAtual === null) {
            $expressaoAtual = $formula !== null ? $formula['expressao'] : '';
        }

        if ($casasDecimaisAtual === null) {
            $casasDecimaisAtual = FormulaPontuacaoRepository::casasDecimais($formula);
        }

        $this->renderizar('admin/formulas/etapa', [
            'erro' => $erro,
            'etapa' => $etapa,
            'expressaoAtual' => $expressaoAtual,
            'casasDecimaisAtual' => $casasDecimaisAtual,
            'criteriosDaEtapa' => $criteriosDaEtapa,
            'resultadoTeste' => $resultadoTeste,
        ], 'Fórmula de pontuação: ' . $etapa['nome'], ['tipo' => 'formula_etapa', 'id' => (int) $etapaId]);
    }

    public function trilha($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $etapasDaTrilha = $this->etapas->listarPorTrilha($trilhaId);
        $variaveisPermitidas = array_map(function ($etapa) {
            return 'NE' . (int) $etapa['ordem'];
        }, $etapasDaTrilha);

        $erro = null;
        $resultadoTeste = null;
        $expressaoAtual = null;
        $casasDecimaisAtual = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $expressaoAtual = trim(isset($_POST['expressao']) ? $_POST['expressao'] : '');
            $casasDecimaisAtual = isset($_POST['casas_decimais']) ? (int) $_POST['casas_decimais'] : 2;
            $acao = isset($_POST['acao']) ? $_POST['acao'] : 'salvar';

            if ($acao === 'testar') {
                $resultadoTeste = $this->testar($expressaoAtual, $variaveisPermitidas);
            } else {
                $validacao = ExpressaoAritmetica::validar($expressaoAtual, $variaveisPermitidas);

                if (!$validacao['valido']) {
                    $erro = $validacao['mensagem'];
                } elseif ($casasDecimaisAtual < 0 || $casasDecimaisAtual > 4) {
                    $erro = 'Casas decimais precisa ser um número entre 0 e 4.';
                } else {
                    $this->formulas->salvarParaTrilha($trilhaId, $expressaoAtual, $casasDecimaisAtual);
                    $this->redirecionar('apuracao/index/' . (int) $trilhaId);
                    return;
                }
            }
        }

        $formula = $this->formulas->buscarPorTrilha($trilhaId);

        if ($expressaoAtual === null) {
            $expressaoAtual = $formula !== null ? $formula['expressao'] : '';
        }

        if ($casasDecimaisAtual === null) {
            $casasDecimaisAtual = FormulaPontuacaoRepository::casasDecimais($formula);
        }

        $this->renderizar('admin/formulas/trilha', [
            'erro' => $erro,
            'trilha' => $trilha,
            'expressaoAtual' => $expressaoAtual,
            'casasDecimaisAtual' => $casasDecimaisAtual,
            'etapasDaTrilha' => $etapasDaTrilha,
            'resultadoTeste' => $resultadoTeste,
        ], 'Fórmula da nota final: ' . $trilha['nome'], ['tipo' => 'apuracao', 'id' => (int) $trilhaId]);
    }

    private function testar($expressao, array $variaveisPermitidas)
    {
        $valores = [];

        foreach ($variaveisPermitidas as $variavel) {
            $bruto = isset($_POST['valores'][$variavel]) ? $_POST['valores'][$variavel] : '0';
            $valores[$variavel] = (float) str_replace(',', '.', $bruto);
        }

        try {
            return ['sucesso' => true, 'valor' => ExpressaoAritmetica::avaliar($expressao, $valores)];
        } catch (\RuntimeException $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }
}
