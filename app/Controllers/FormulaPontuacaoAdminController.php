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
            exit('Etapa nao encontrada.');
        }

        $criteriosDaEtapa = $this->criterios->listarPorEtapa($etapaId);
        $variaveisPermitidas = array_column($criteriosDaEtapa, 'codigo');

        $erro = null;
        $resultadoTeste = null;
        $expressaoAtual = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $expressaoAtual = trim(isset($_POST['expressao']) ? $_POST['expressao'] : '');
            $acao = isset($_POST['acao']) ? $_POST['acao'] : 'salvar';

            if ($acao === 'testar') {
                $resultadoTeste = $this->testar($expressaoAtual, $variaveisPermitidas);
            } else {
                $validacao = ExpressaoAritmetica::validar($expressaoAtual, $variaveisPermitidas);

                if (!$validacao['valido']) {
                    $erro = $validacao['mensagem'];
                } else {
                    $this->formulas->salvarParaEtapa($etapaId, $expressaoAtual);
                    $this->redirecionar('etapas/index/' . (int) $etapa['trilha_id']);
                    return;
                }
            }
        }

        if ($expressaoAtual === null) {
            $formula = $this->formulas->buscarPorEtapa($etapaId);
            $expressaoAtual = $formula !== null ? $formula['expressao'] : '';
        }

        $this->renderizar('admin/formulas/etapa', [
            'erro' => $erro,
            'etapa' => $etapa,
            'expressaoAtual' => $expressaoAtual,
            'criteriosDaEtapa' => $criteriosDaEtapa,
            'resultadoTeste' => $resultadoTeste,
        ], 'Formula de pontuacao — ' . $etapa['nome']);
    }

    public function trilha($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha nao encontrada.');
        }

        $etapasDaTrilha = $this->etapas->listarPorTrilha($trilhaId);
        $variaveisPermitidas = array_map(function ($etapa) {
            return 'NE' . (int) $etapa['ordem'];
        }, $etapasDaTrilha);

        $erro = null;
        $resultadoTeste = null;
        $expressaoAtual = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $expressaoAtual = trim(isset($_POST['expressao']) ? $_POST['expressao'] : '');
            $acao = isset($_POST['acao']) ? $_POST['acao'] : 'salvar';

            if ($acao === 'testar') {
                $resultadoTeste = $this->testar($expressaoAtual, $variaveisPermitidas);
            } else {
                $validacao = ExpressaoAritmetica::validar($expressaoAtual, $variaveisPermitidas);

                if (!$validacao['valido']) {
                    $erro = $validacao['mensagem'];
                } else {
                    $this->formulas->salvarParaTrilha($trilhaId, $expressaoAtual);
                    $this->redirecionar('trilhas/index/' . (int) $trilha['concurso_id']);
                    return;
                }
            }
        }

        if ($expressaoAtual === null) {
            $formula = $this->formulas->buscarPorTrilha($trilhaId);
            $expressaoAtual = $formula !== null ? $formula['expressao'] : '';
        }

        $this->renderizar('admin/formulas/trilha', [
            'erro' => $erro,
            'trilha' => $trilha,
            'expressaoAtual' => $expressaoAtual,
            'etapasDaTrilha' => $etapasDaTrilha,
            'resultadoTeste' => $resultadoTeste,
        ], 'Formula da nota final — ' . $trilha['nome']);
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
