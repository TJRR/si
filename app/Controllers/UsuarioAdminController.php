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
        $pendentes = $this->usuarios->listarPendentes();
        $this->renderizar('admin/usuarios', [
            'pendentes' => $pendentes,
            'perfis' => $this->perfis->listar(),
            'concursos' => $this->concursos->listar(),
        ], 'Cadastros pendentes');
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
}
