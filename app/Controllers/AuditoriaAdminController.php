<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\LogAuditoriaRepository;
use App\Repositories\UsuarioRepository;

class AuditoriaAdminController extends Controller
{
    private $logs;
    private $usuarios;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->logs = new LogAuditoriaRepository();
        $this->usuarios = new UsuarioRepository();
    }

    public function index()
    {
        $filtros = [
            'usuario_id' => (isset($_GET['usuario_id']) && $_GET['usuario_id'] !== '') ? (int) $_GET['usuario_id'] : null,
            'acao' => isset($_GET['acao']) && $_GET['acao'] !== '' ? trim($_GET['acao']) : null,
            'data_inicio' => isset($_GET['data_inicio']) && $_GET['data_inicio'] !== '' ? $_GET['data_inicio'] : null,
            'data_fim' => isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : null,
        ];

        $pagina = max(1, (int) (isset($_GET['pagina']) ? $_GET['pagina'] : 1));
        $porPagina = 50;
        $total = $this->logs->contar($filtros);
        $registros = $this->logs->listar($filtros, $porPagina, ($pagina - 1) * $porPagina);

        $this->renderizar('admin/auditoria/index', [
            'registros' => $registros,
            'usuarios' => $this->usuarios->listarTodos(),
            'acoesDisponiveis' => $this->logs->listarAcoesDistintas(),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => max(1, (int) ceil($total / $porPagina)),
        ], 'Auditoria');
    }
}
