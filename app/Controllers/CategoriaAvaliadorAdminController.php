<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\CategoriaAvaliadorRepository;
use App\Repositories\ConcursoRepository;

class CategoriaAvaliadorAdminController extends Controller
{
    private $categorias;
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->categorias = new CategoriaAvaliadorRepository();
        $this->concursos = new ConcursoRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/categorias_avaliador/index', [
            'concurso' => $concurso,
            'categorias' => $this->categorias->listarPorConcurso($concursoId),
        ], 'Categorias de avaliador — ' . $concurso['nome'], ['tipo' => 'categorias_avaliador', 'id' => (int) $concursoId]);
    }

    public function novo($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome da categoria.';
            } elseif ($this->categorias->nomeJaExisteNoConcurso($concursoId, $nome)) {
                $erro = 'Já existe uma categoria com este nome neste concurso.';
            } else {
                $this->categorias->criar($concursoId, $nome);
                $this->redirecionar('categoriasAvaliador/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/categorias_avaliador/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'categoria' => null,
        ], 'Nova categoria de avaliador', ['tipo' => 'categorias_avaliador', 'id' => (int) $concursoId]);
    }

    public function editar($id)
    {
        $categoria = $this->categorias->buscarPorId($id);

        if ($categoria === null) {
            http_response_code(404);
            exit('Categoria não encontrada.');
        }

        $concurso = $this->concursos->buscarPorId($categoria['concurso_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome da categoria.';
            } elseif ($this->categorias->nomeJaExisteNoConcurso($categoria['concurso_id'], $nome, $id)) {
                $erro = 'Já existe uma categoria com este nome neste concurso.';
            } else {
                $this->categorias->atualizar($id, $nome);
                $categoria = $this->categorias->buscarPorId($id);
            }
        }

        $this->renderizar('admin/categorias_avaliador/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'categoria' => $categoria,
        ], 'Editar categoria de avaliador', ['tipo' => 'categorias_avaliador', 'id' => (int) $concurso['id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);

        $this->categorias->remover($id);
        $this->redirecionar('categoriasAvaliador/index/' . $concursoId);
    }
}
