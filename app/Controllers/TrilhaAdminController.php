<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\TrilhaRepository;

class TrilhaAdminController extends Controller
{
    private $trilhas;
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->trilhas = new TrilhaRepository();
        $this->concursos = new ConcursoRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso nao encontrado.');
        }

        $lista = $this->trilhas->listarPorConcurso($concursoId);
        $this->renderizar('admin/trilhas/index', [
            'concurso' => $concurso,
            'trilhas' => $lista,
        ], 'Trilhas de ' . $concurso['nome']);
    }

    public function novo($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso nao encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');
            $ordem = (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0);
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if ($nome === '') {
                $erro = 'Informe o nome da trilha.';
            } else {
                $this->trilhas->criar($concursoId, $nome, $descricao, $ordem, $ativo);
                $this->redirecionar('trilhas/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/trilhas/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'trilha' => null,
        ], 'Nova trilha');
    }

    public function editar($id)
    {
        $trilha = $this->trilhas->buscarPorId($id);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha nao encontrada.');
        }

        $concurso = $this->concursos->buscarPorId($trilha['concurso_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');
            $ordem = (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0);
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if ($nome === '') {
                $erro = 'Informe o nome da trilha.';
            } else {
                $this->trilhas->atualizar($id, $nome, $descricao, $ordem, $ativo);
                $trilha = $this->trilhas->buscarPorId($id);
            }
        }

        $this->renderizar('admin/trilhas/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'trilha' => $trilha,
        ], 'Editar trilha');
    }
}
