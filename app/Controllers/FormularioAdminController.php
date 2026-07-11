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
        ], 'Formulários de ' . $concurso['nome'], ['tipo' => 'formularios', 'id' => (int) $concursoId]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);

        try {
            $this->formularios->remover($id);
            $_SESSION['flash'] = 'Formulário removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: este formulário já tem campos, etapas vinculadas ou submissões.'
                : 'Não foi possível remover o formulário.';
        }

        $this->redirecionar('formularios/index/' . $concursoId);
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
        ], 'Novo formulário', ['tipo' => 'formularios', 'id' => (int) $concursoId]);
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
        ], 'Editar formulário', ['tipo' => 'formularios', 'id' => (int) $concurso['id']]);
    }

    public function publicar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $formulario = $this->formularios->buscarPorId($id);
        $resultado = (new FormularioDinamicoService())->publicar($id);

        if (!$resultado['sucesso']) {
            $_SESSION['flash'] = $resultado['mensagem'];
        }

        $this->redirecionar($this->destinoAposAcao($formulario));
    }

    public function despublicar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $formulario = $this->formularios->buscarPorId($id);
        (new FormularioDinamicoService())->despublicar($id);
        $this->redirecionar($this->destinoAposAcao($formulario));
    }

    public function arquivar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $formulario = $this->formularios->buscarPorId($id);
        (new FormularioDinamicoService())->arquivar($id);
        $this->redirecionar($this->destinoAposAcao($formulario));
    }

    public function desarquivar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $formulario = $this->formularios->buscarPorId($id);
        (new FormularioDinamicoService())->desarquivar($id);
        $this->redirecionar($this->destinoAposAcao($formulario));
    }

    /**
     * Publicar/despublicar/arquivar/desarquivar podem ser disparados tanto da
     * lista de Formularios quanto da aba "Formulario vinculado" de uma Etapa
     * — volta pra onde a acao veio, usando o etapa_id (so enviado por essa
     * aba) como sinal de onde a requisicao se originou.
     */
    private function destinoAposAcao(array $formulario)
    {
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        return $etapaId > 0
            ? 'etapas/formularioVinculado/' . $etapaId
            : 'formularios/index/' . (int) $formulario['concurso_id'];
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
}
