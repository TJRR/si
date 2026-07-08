<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\TrilhaRepository;

class EtapaAdminController extends Controller
{
    private $etapas;
    private $trilhas;
    private $formularios;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->formularios = new FormularioDinamicoRepository();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha nao encontrada.');
        }

        $lista = $this->etapas->listarPorTrilha($trilhaId);
        $this->renderizar('admin/etapas/index', [
            'trilha' => $trilha,
            'etapas' => $lista,
        ], 'Etapas de ' . $trilha['nome']);
    }

    public function novo($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha nao encontrada.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();

            if ($dados['nome'] === '') {
                $erro = 'Informe o nome da etapa.';
            } else {
                $this->etapas->criar(
                    $trilhaId,
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['ordem'],
                    $dados['data_inicio'],
                    $dados['data_fim'],
                    $dados['formulario_dinamico_id'],
                    $dados['regra_transicao_tipo'],
                    $dados['regra_transicao_valor']
                );
                $this->redirecionar('etapas/index/' . $trilhaId);
                return;
            }
        }

        $this->renderizar('admin/etapas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'etapa' => null,
            'formularios' => $this->formularios->listar(),
        ], 'Nova etapa');
    }

    public function editar($id)
    {
        $etapa = $this->etapas->buscarPorId($id);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa nao encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();

            if ($dados['nome'] === '') {
                $erro = 'Informe o nome da etapa.';
            } else {
                $this->etapas->atualizar(
                    $id,
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['ordem'],
                    $dados['data_inicio'],
                    $dados['data_fim'],
                    $dados['formulario_dinamico_id'],
                    $dados['regra_transicao_tipo'],
                    $dados['regra_transicao_valor']
                );
                $etapa = $this->etapas->buscarPorId($id);
            }
        }

        $this->renderizar('admin/etapas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'etapa' => $etapa,
            'formularios' => $this->formularios->listar(),
        ], 'Editar etapa');
    }

    private function lerDadosFormulario()
    {
        return [
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'descricao' => trim(isset($_POST['descricao']) ? $_POST['descricao'] : ''),
            'ordem' => (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0),
            'data_inicio' => isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '',
            'data_fim' => isset($_POST['data_fim']) ? $_POST['data_fim'] : '',
            'formulario_dinamico_id' => isset($_POST['formulario_dinamico_id']) ? $_POST['formulario_dinamico_id'] : '',
            'regra_transicao_tipo' => isset($_POST['regra_transicao_tipo']) ? $_POST['regra_transicao_tipo'] : '',
            'regra_transicao_valor' => isset($_POST['regra_transicao_valor']) ? str_replace(',', '.', $_POST['regra_transicao_valor']) : '',
        ];
    }
}
