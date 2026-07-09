<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConfiguracaoVisualRepository;

class TemaAdminController extends Controller
{
    private $configuracaoVisual;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->configuracaoVisual = new ConfiguracaoVisualRepository();
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['cor_primaria_inicio']) && !empty($_POST['cor_primaria_fim'])) {
                $this->configuracaoVisual->atualizar(
                    trim($_POST['cor_primaria_inicio']),
                    trim($_POST['cor_primaria_fim'])
                );
            }

            $_SESSION['flash'] = 'Tema atualizado.';
            $this->redirecionar('tema/index');
            return;
        }

        $this->renderizar('admin/tema/form', [
            'configuracaoVisual' => $this->configuracaoVisual->buscar(),
        ], 'Tema');
    }
}
