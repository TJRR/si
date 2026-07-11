<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\TrilhaRepository;

class TrilhaAdminController extends Controller
{
    private $trilhas;
    private $concursos;
    private $etapas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->trilhas = new TrilhaRepository();
        $this->concursos = new ConcursoRepository();
        $this->etapas = new EtapaRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $lista = $this->trilhas->listarPorConcurso($concursoId);

        foreach ($lista as &$trilha) {
            $etapaCadastro = $this->etapas->buscarCadastroDaTrilha($trilha['id']);
            $trilha['etapa_cadastro_id'] = $etapaCadastro !== null ? $etapaCadastro['id'] : null;
            $trilha['inscricoes_abertas'] = $etapaCadastro !== null && (bool) $etapaCadastro['captura_ativa'];
        }
        unset($trilha);

        $this->renderizar('admin/trilhas/index', [
            'concurso' => $concurso,
            'trilhas' => $lista,
        ], 'Trilhas de ' . $concurso['nome'], ['tipo' => 'trilhas', 'id' => (int) $concursoId]);
    }

    public function alternarInscricoes($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $etapaCadastro = $this->etapas->buscarCadastroDaTrilha($trilhaId);

        if ($etapaCadastro === null) {
            $_SESSION['flash'] = 'Esta trilha não tem etapa "Cadastro de Equipe" (ordem 1) configurada.';
        } else {
            $this->etapas->alternarCapturaAtiva($etapaCadastro['id']);
        }

        $this->redirecionar('trilhas/index/' . (int) $trilha['concurso_id']);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);

        try {
            $this->trilhas->remover($id);
            $_SESSION['flash'] = 'Trilha removida.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: esta trilha já tem etapas, equipes, fórmula ou regras de desempate vinculadas.'
                : 'Não foi possível remover a trilha.';
        }

        $this->redirecionar('trilhas/index/' . $concursoId);
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
        ], 'Nova trilha', ['tipo' => 'trilhas', 'id' => (int) $concursoId]);
    }

    public function editar($id)
    {
        $trilha = $this->trilhas->buscarPorId($id);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
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
        ], 'Editar trilha', ['tipo' => 'trilha', 'id' => (int) $id]);
    }
}
