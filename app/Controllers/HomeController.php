<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;

class HomeController extends Controller
{
    public function index()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->renderizar('home/index', [], 'Painel');
    }

    /**
     * Rota de exemplo para validar o controle de acesso por concurso
     * (papel 'avaliador' restrito a um concurso especifico, nao global).
     */
    public function painel($concursoId = null)
    {
        RoleMiddleware::exigir(['administrador', 'avaliador'], $concursoId);
        $this->renderizar('home/index', ['concursoId' => $concursoId], 'Painel');
    }
}
