<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\FormularioDinamicoRepository;
use App\Services\FormularioDinamicoService;

class FormularioAdminController extends Controller
{
    private $formularios;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->formularios = new FormularioDinamicoRepository();
    }

    public function index()
    {
        $lista = $this->formularios->listar();
        $this->renderizar('admin/formularios/index', ['formularios' => $lista], 'Formularios Dinamicos');
    }

    public function novo()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome do formulario.';
            } else {
                $id = $this->formularios->criar($nome, $descricao);
                $this->redirecionar('campos/index/' . $id);
                return;
            }
        }

        $this->renderizar('admin/formularios/form', [
            'erro' => $erro,
            'formulario' => null,
        ], 'Novo formulario');
    }

    public function editar($id)
    {
        $formulario = $this->formularios->buscarPorId($id);

        if ($formulario === null) {
            http_response_code(404);
            exit('Formulario nao encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome do formulario.';
            } else {
                $this->formularios->atualizar($id, $nome, $descricao);
                $formulario = $this->formularios->buscarPorId($id);
            }
        }

        $this->renderizar('admin/formularios/form', [
            'erro' => $erro,
            'formulario' => $formulario,
        ], 'Editar formulario');
    }

    public function publicar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $resultado = (new FormularioDinamicoService())->publicar($id);
        $this->redirecionarComMensagem($id, $resultado);
    }

    public function arquivar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        (new FormularioDinamicoService())->arquivar($id);
        $this->redirecionar('formularios/index');
    }

    public function duplicar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $resultado = (new FormularioDinamicoService())->duplicar($id);

        if ($resultado['sucesso']) {
            $this->redirecionar('campos/index/' . $resultado['novo_id']);
            return;
        }

        $this->redirecionar('formularios/index');
    }

    private function redirecionarComMensagem($id, array $resultado)
    {
        // Mensagens de sucesso/erro simples via flash de sessao (sem lib externa).
        $_SESSION['flash'] = $resultado['sucesso'] ? null : $resultado['mensagem'];
        $this->redirecionar('formularios/index');
    }
}
