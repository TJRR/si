<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConteudoSiteRepository;

class ConteudoAdminController extends Controller
{
    private $conteudos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->conteudos = new ConteudoSiteRepository();
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST['conteudo'] as $chave => $valor) {
                $this->conteudos->atualizarValor($chave, trim($valor));
            }
            $_SESSION['flash'] = 'Conteudo do site atualizado.';
            $this->redirecionar('conteudo/index');
            return;
        }

        $this->renderizar('admin/conteudo/form', [
            'conteudos' => $this->conteudos->listar(),
        ], 'Conteudo do site publico');
    }
}
