<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Services\FormularioDinamicoService;

class FormularioAdminController extends Controller
{
    private $formularios;
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->formularios = new FormularioDinamicoRepository();
        $this->concursos = new ConcursoRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->buscarConcursoOu404($concursoId);
        $lista = $this->formularios->listar($concursoId);

        $this->renderizar('admin/formularios/index', [
            'concurso' => $concurso,
            'concursos' => $this->concursos->listar(),
            'formularios' => $lista,
            'breadcrumb' => $this->montarBreadcrumb($concurso),
        ], 'Formulários de ' . $concurso['nome']);
    }

    public function novo($concursoId)
    {
        $concurso = $this->buscarConcursoOu404($concursoId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome do formulário.';
            } else {
                $id = $this->formularios->criar($concursoId, $nome, $descricao);
                $this->redirecionar('campos/index/' . $id);
                return;
            }
        }

        $this->renderizar('admin/formularios/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'formulario' => null,
            'breadcrumb' => $this->montarBreadcrumb($concurso, 'Novo formulário'),
        ], 'Novo formulário');
    }

    public function editar($id)
    {
        $formulario = $this->formularios->buscarPorId($id);

        if ($formulario === null) {
            http_response_code(404);
            exit('Formulário não encontrado.');
        }

        $concurso = $this->concursos->buscarPorId($formulario['concurso_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome do formulário.';
            } else {
                $this->formularios->atualizar($id, $nome, $descricao);
                $formulario = $this->formularios->buscarPorId($id);
            }
        }

        $this->renderizar('admin/formularios/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'formulario' => $formulario,
            'breadcrumb' => $this->montarBreadcrumb($concurso, 'Editar ' . $formulario['nome']),
        ], 'Editar formulário');
    }

    public function publicar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $formulario = $this->formularios->buscarPorId($id);
        $resultado = (new FormularioDinamicoService())->publicar($id);
        $this->redirecionarComMensagem($formulario, $resultado);
    }

    public function arquivar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $formulario = $this->formularios->buscarPorId($id);
        (new FormularioDinamicoService())->arquivar($id);
        $this->redirecionar('formularios/index/' . (int) $formulario['concurso_id']);
    }

    public function duplicar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoOrigemId = (int) (isset($_POST['concurso_origem_id']) ? $_POST['concurso_origem_id'] : 0);
        $concursoDestinoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $resultado = (new FormularioDinamicoService())->duplicar($id, $concursoDestinoId);

        if ($resultado['sucesso']) {
            $this->redirecionar('campos/index/' . $resultado['novo_id']);
            return;
        }

        $this->redirecionar('formularios/index/' . $concursoOrigemId);
    }

    private function buscarConcursoOu404($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        return $concurso;
    }

    private function montarBreadcrumb(array $concurso, $itemAtual = null)
    {
        $breadcrumb = [
            ['rotulo' => 'Concursos', 'url' => 'concursos/index'],
            ['rotulo' => $concurso['nome'], 'url' => 'trilhas/index/' . (int) $concurso['id']],
            ['rotulo' => 'Formulários', 'url' => 'formularios/index/' . (int) $concurso['id']],
        ];

        if ($itemAtual !== null) {
            $breadcrumb[] = ['rotulo' => $itemAtual];
        }

        return $breadcrumb;
    }

    private function redirecionarComMensagem($formulario, array $resultado)
    {
        // Mensagens de sucesso/erro simples via flash de sessao (sem lib externa).
        $_SESSION['flash'] = $resultado['sucesso'] ? null : $resultado['mensagem'];
        $this->redirecionar('formularios/index/' . (int) $formulario['concurso_id']);
    }
}
