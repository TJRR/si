<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Services\NavegacaoService;

/**
 * Endpoint JSON usado pelo JS da arvore lateral (assets/js/navegacao-arvore.js)
 * para carregar os filhos de um no sob demanda, sem consultar tudo de uma vez.
 */
class NavegacaoController extends Controller
{
    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
    }

    public function filhos($tipo, $id)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(NavegacaoService::filhosDe($tipo, (int) $id));
    }
}
