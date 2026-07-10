<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\UsuarioRepository;
use App\Services\AcessoParticipanteService;

class UsuarioAdminController extends Controller
{
    private $usuarios;
    private $perfis;
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->usuarios = new UsuarioRepository();
        $this->perfis = new PerfilRepository();
        $this->concursos = new ConcursoRepository();
    }

    public function index()
    {
        $filtroConcursoId = (isset($_GET['concurso_id']) && $_GET['concurso_id'] !== '') ? (int) $_GET['concurso_id'] : null;
        $lista = $this->usuarios->listarTodos($filtroConcursoId);

        foreach ($lista as &$usuario) {
            $usuario['perfis'] = $this->usuarios->perfisDoUsuario($usuario['id']);
        }
        unset($usuario);

        $this->renderizar('admin/usuarios', [
            'usuarios' => $lista,
            'perfis' => $this->perfis->listar(),
            'concursos' => $this->concursos->listar(),
            'filtroConcursoId' => $filtroConcursoId,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Usuários');

        unset($_SESSION['flash']);
    }

    public function aprovar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $perfilChave = isset($_POST['perfil']) ? $_POST['perfil'] : '';
        $concursoId = (isset($_POST['concurso_id']) && $_POST['concurso_id'] !== '') ? (int) $_POST['concurso_id'] : null;

        $perfil = $this->perfis->buscarPorChave($perfilChave);

        if ($perfil === null) {
            $_SESSION['flash'] = 'Selecione um perfil válido antes de aprovar.';
            $this->redirecionar('usuarios/index');
            return;
        }

        $this->usuarios->atualizarStatus($id, 'aprovado');
        $this->perfis->atribuir($id, $perfil['id'], $concursoId);
        $this->redirecionar('usuarios/index');
    }

    public function rejeitar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarStatus($id, 'rejeitado');
        $this->redirecionar('usuarios/index');
    }

    public function suspender()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarAtivo($id, false);
        $this->redirecionar('usuarios/index');
    }

    public function reativar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarAtivo($id, true);
        $this->redirecionar('usuarios/index');
    }

    public function convidar()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $perfilChave = isset($_POST['perfil']) ? $_POST['perfil'] : '';
            $concursoId = (isset($_POST['concurso_id']) && $_POST['concurso_id'] !== '') ? (int) $_POST['concurso_id'] : null;

            $perfil = $this->perfis->buscarPorChave($perfilChave);

            if ($nome === '' || $email === '') {
                $erro = 'Informe nome e e-mail.';
            } elseif ($perfil === null) {
                $erro = 'Selecione um perfil válido.';
            } else {
                (new AcessoParticipanteService())->convidarUsuario($nome, $email, $perfil['id'], $concursoId);
                $_SESSION['flash'] = 'Usuário convidado com sucesso.';
                $this->redirecionar('usuarios/index');
                return;
            }
        }

        $this->renderizar('admin/usuarios_convidar', [
            'erro' => $erro,
            'perfis' => $this->perfis->listar(),
            'concursos' => $this->concursos->listar(),
        ], 'Convidar usuário');
    }
}
