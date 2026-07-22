<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\HomeSecaoOrdemRepository;

/**
 * Fase 19 (#97): ordenacao das secoes do meio da home (aba "Ordenação"
 * de Configuração).
 */
class HomeSecaoOrdemAdminController extends Controller
{
    private $secoes;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->secoes = new HomeSecaoOrdemRepository();
    }

    public function index()
    {
        $this->renderizar('admin/ordenacao_home/index', [
            'secoes' => $this->secoes->listarOrdenado(),
        ], 'Ordenação', ['tipo' => 'configuracaoOrdenacao', 'id' => null]);
    }

    public function reordenar()
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->secoes->reordenar($ids);

        echo json_encode(['ok' => true]);
    }
}
