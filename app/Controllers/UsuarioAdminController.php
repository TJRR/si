<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\UsuarioRepository;

class UsuarioAdminController extends Controller
{
    private $usuarios;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->usuarios = new UsuarioRepository();
    }

    public function index()
    {
        $pendentes = $this->usuarios->listarPendentes();
        $this->renderizar('admin/usuarios', ['pendentes' => $pendentes], 'Cadastros pendentes');
    }

    public function aprovar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarStatus($id, 'aprovado');
        $this->redirecionar('usuarios/index');
    }

    public function rejeitar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->usuarios->atualizarStatus($id, 'rejeitado');
        $this->redirecionar('usuarios/index');
    }
}
