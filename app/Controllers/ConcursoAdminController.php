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
        // Fase 29 (ajuste pos-push): exigirEmQualquerConcurso() na entrada +
        // exigir() com o concurso resolvido dentro de editar()/remover() -
        // exigir() sem concurso so' reconhece vinculo GLOBAL. index() fica
        // sem filtro por concurso de proposito (e' uma listagem geral, mesmo
        // padrao ja usado no dashboard administrativo) - quem for escopado
        // consegue ver o nome de concursos de fora do escopo dele na lista,
        // mas nao consegue abrir/editar/remover nenhum deles.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
        $this->concursos = new ConcursoRepository();
    }

    public function index()
    {
        $lista = $this->concursos->listar();
        $this->renderizar('admin/concursos/index', ['concursos' => $lista], 'Concursos');
    }

    public function novo()
    {
        // Criar concurso NOVO fica exclusivo de administrador GLOBAL de
        // proposito (nao exigirEmQualquerConcurso) - um administrador
        // escopado a um concurso existente nao tem autoridade pra criar
        // outro concurso do zero, que ele ainda nao esta' escopado a nada.
        RoleMiddleware::exigir(['administrador']);
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

        RoleMiddleware::exigir(['administrador', 'suporte'], $concurso['id']);

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador'], $concurso['id']);
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

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concurso = $this->concursos->buscarPorId($id);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        RoleMiddleware::exigir(['administrador'], $concurso['id']);

        try {
            $this->concursos->remover($id);
            $_SESSION['flash'] = 'Concurso removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: este concurso já tem trilhas, formulários ou categorias de avaliador vinculados.'
                : 'Não foi possível remover o concurso.';
        }

        $this->redirecionar('concursos/index');
    }
}
