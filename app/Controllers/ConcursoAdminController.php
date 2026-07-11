<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;

class ConcursoAdminController extends Controller
{
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->concursos = new ConcursoRepository();
    }

    public function index()
    {
        $lista = $this->concursos->listar();
        $this->renderizar('admin/concursos/index', ['concursos' => $lista], 'Concursos');
    }

    public function novo()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');
            $dataInicio = isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '';
            $dataFim = isset($_POST['data_fim']) ? $_POST['data_fim'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'rascunho';

            if ($nome === '') {
                $erro = 'Informe o nome do concurso.';
            } else {
                $id = $this->concursos->criar($nome, $descricao, $dataInicio, $dataFim, $status);
                $this->redirecionar('trilhas/index/' . $id);
                return;
            }
        }

        $this->renderizar('admin/concursos/form', [
            'erro' => $erro,
            'concurso' => null,
        ], 'Novo concurso');
    }

    public function editar($id)
    {
        $concurso = $this->concursos->buscarPorId($id);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');
            $dataInicio = isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '';
            $dataFim = isset($_POST['data_fim']) ? $_POST['data_fim'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'rascunho';

            if ($nome === '') {
                $erro = 'Informe o nome do concurso.';
            } else {
                $this->concursos->atualizar($id, $nome, $descricao, $dataInicio, $dataFim, $status);
                $concurso = $this->concursos->buscarPorId($id);
            }
        }

        $this->renderizar('admin/concursos/form', [
            'erro' => $erro,
            'concurso' => $concurso,
        ], 'Editar concurso', ['tipo' => 'concurso', 'id' => (int) $id]);
    }
}
