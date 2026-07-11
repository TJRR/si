<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\CriterioAvaliacaoRepository;
use App\Repositories\EtapaRepository;

class CriterioAvaliacaoAdminController extends Controller
{
    private $criterios;
    private $etapas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->criterios = new CriterioAvaliacaoRepository();
        $this->etapas = new EtapaRepository();
    }

    public function index($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $lista = $this->criterios->listarPorEtapa($etapaId);
        $this->renderizar('admin/criterios/index', [
            'etapa' => $etapa,
            'criterios' => $lista,
            'somaPesos' => $this->criterios->somaPesosPorEtapa($etapaId),
        ], 'Critérios de ' . $etapa['nome'], ['tipo' => 'criterios', 'id' => (int) $etapaId]);
    }

    public function novo($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();

            if ($dados['nome'] === '') {
                $erro = 'Informe o nome do critério.';
            } elseif ($dados['codigo'] === '') {
                $erro = 'Informe o código do critério.';
            } elseif ($this->criterios->codigoJaExisteNaEtapa($etapaId, $dados['codigo'])) {
                $erro = 'Já existe um critério com este código nesta etapa.';
            } else {
                $this->criterios->criar(
                    $etapaId,
                    $dados['codigo'],
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['peso'],
                    $dados['escala_min'],
                    $dados['escala_max']
                );
                $this->redirecionar('criterios/index/' . $etapaId);
                return;
            }
        }

        $codigoSugerido = 'C' . ($this->criterios->contarPorEtapa($etapaId) + 1);

        $this->renderizar('admin/criterios/form', [
            'erro' => $erro,
            'etapa' => $etapa,
            'criterio' => null,
            'codigoSugerido' => $codigoSugerido,
        ], 'Novo critério', ['tipo' => 'criterios', 'id' => (int) $etapaId]);
    }

    public function editar($id)
    {
        $criterio = $this->criterios->buscarPorId($id);

        if ($criterio === null) {
            http_response_code(404);
            exit('Critério não encontrado.');
        }

        $etapa = $this->etapas->buscarPorId($criterio['etapa_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();

            if ($dados['nome'] === '') {
                $erro = 'Informe o nome do critério.';
            } elseif ($dados['codigo'] === '') {
                $erro = 'Informe o código do critério.';
            } elseif ($this->criterios->codigoJaExisteNaEtapa($criterio['etapa_id'], $dados['codigo'], $id)) {
                $erro = 'Já existe um critério com este código nesta etapa.';
            } else {
                $this->criterios->atualizar(
                    $id,
                    $dados['codigo'],
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['peso'],
                    $dados['escala_min'],
                    $dados['escala_max']
                );
                $criterio = $this->criterios->buscarPorId($id);
            }
        }

        $this->renderizar('admin/criterios/form', [
            'erro' => $erro,
            'etapa' => $etapa,
            'criterio' => $criterio,
        ], 'Editar critério', ['tipo' => 'criterios', 'id' => (int) $etapa['id']]);
    }

    public function mover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $direcao = isset($_POST['direcao']) ? $_POST['direcao'] : 'cima';
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        $this->criterios->mover($id, $direcao);
        $this->redirecionar('criterios/index/' . $etapaId);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        $this->criterios->remover($id);
        $this->redirecionar('criterios/index/' . $etapaId);
    }

    private function lerDadosFormulario()
    {
        return [
            'codigo' => trim(isset($_POST['codigo']) ? $_POST['codigo'] : ''),
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'descricao' => trim(isset($_POST['descricao']) ? $_POST['descricao'] : ''),
            'peso' => (float) (isset($_POST['peso']) ? str_replace(',', '.', $_POST['peso']) : 1),
            'escala_min' => (float) (isset($_POST['escala_min']) ? str_replace(',', '.', $_POST['escala_min']) : 0),
            'escala_max' => (float) (isset($_POST['escala_max']) ? str_replace(',', '.', $_POST['escala_max']) : 10),
        ];
    }
}
