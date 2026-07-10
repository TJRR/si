<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Services\CampoDinamicoService;

class CampoAdminController extends Controller
{
    private $campos;
    private $formularios;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->campos = new CampoDinamicoRepository();
        $this->formularios = new FormularioDinamicoRepository();
    }

    public function index($formularioId)
    {
        $formulario = $this->formularios->buscarPorId($formularioId);

        if ($formulario === null) {
            http_response_code(404);
            exit('Formulário não encontrado.');
        }

        $lista = $this->campos->listarPorFormulario($formularioId);
        $this->renderizar('admin/formularios/campos', [
            'formulario' => $formulario,
            'campos' => $lista,
        ], 'Campos de ' . $formulario['nome']);
    }

    public function novo($formularioId)
    {
        $formulario = $this->formularios->buscarPorId($formularioId);

        if ($formulario === null) {
            http_response_code(404);
            exit('Formulário não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();
            $resultado = (new CampoDinamicoService())->criar(
                $formularioId,
                $dados['rotulo'],
                $dados['tipo'],
                $dados['obrigatorio'],
                $dados['config']
            );

            if ($resultado['sucesso']) {
                $this->redirecionar('campos/index/' . $formularioId);
                return;
            }

            $erro = $resultado['mensagem'];
        }

        $this->renderizar('admin/campos/form', [
            'erro' => $erro,
            'formulario' => $formulario,
            'campo' => null,
            'tipos' => CampoDinamicoService::TIPOS,
        ], 'Novo campo');
    }

    public function editar($id)
    {
        $campo = $this->campos->buscarPorId($id);

        if ($campo === null) {
            http_response_code(404);
            exit('Campo não encontrado.');
        }

        $formulario = $this->formularios->buscarPorId($campo['formulario_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();
            $resultado = (new CampoDinamicoService())->atualizar(
                $id,
                $dados['rotulo'],
                $dados['tipo'],
                $dados['obrigatorio'],
                $dados['config']
            );

            if ($resultado['sucesso']) {
                $this->redirecionar('campos/index/' . $formulario['id']);
                return;
            }

            $erro = $resultado['mensagem'];
            $campo = $this->campos->buscarPorId($id);
        }

        $this->renderizar('admin/campos/form', [
            'erro' => $erro,
            'formulario' => $formulario,
            'campo' => $campo,
            'tipos' => CampoDinamicoService::TIPOS,
        ], 'Editar campo');
    }

    public function mover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $direcao = isset($_POST['direcao']) ? $_POST['direcao'] : 'cima';
        $formularioId = (int) (isset($_POST['formulario_id']) ? $_POST['formulario_id'] : 0);

        $resultado = (new CampoDinamicoService())->mover($id, $direcao);

        if (!$resultado['sucesso']) {
            $_SESSION['flash'] = $resultado['mensagem'];
        }

        $this->redirecionar('campos/index/' . $formularioId);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $formularioId = (int) (isset($_POST['formulario_id']) ? $_POST['formulario_id'] : 0);

        $resultado = (new CampoDinamicoService())->remover($id);

        if (!$resultado['sucesso']) {
            $_SESSION['flash'] = $resultado['mensagem'];
        }

        $this->redirecionar('campos/index/' . $formularioId);
    }

    private function lerDadosFormulario()
    {
        return [
            'rotulo' => trim(isset($_POST['rotulo']) ? $_POST['rotulo'] : ''),
            'tipo' => isset($_POST['tipo']) ? $_POST['tipo'] : '',
            'obrigatorio' => isset($_POST['obrigatorio']) ? 1 : 0,
            'config' => [
                'minimo_repeticoes' => isset($_POST['minimo_repeticoes']) ? $_POST['minimo_repeticoes'] : null,
                'maximo_repeticoes' => isset($_POST['maximo_repeticoes']) ? $_POST['maximo_repeticoes'] : null,
            ],
        ];
    }
}
