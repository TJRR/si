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

    private static $colunasOrdenaveis = ['criado_em', 'usuario_nome', 'acao', 'entidade', 'ip_origem'];

    public function index()
    {
        $filtros = $this->filtrosDaRequisicao();
        list($ordenar, $direcao) = $this->ordenacaoDaRequisicao();

        $pagina = max(1, (int) (isset($_GET['pagina']) ? $_GET['pagina'] : 1));
        $porPagina = 50;
        $total = $this->logs->contar($filtros);
        $registros = $this->logs->listar($filtros, $porPagina, ($pagina - 1) * $porPagina, $ordenar, $direcao);

        $this->renderizar('admin/auditoria/index', [
            'registros' => $registros,
            'usuarios' => $this->usuarios->listarTodos(),
            'acoesDisponiveis' => $this->logs->listarAcoesDistintas(),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'totalPaginas' => max(1, (int) ceil($total / $porPagina)),
            'total' => $total,
            'ordenar' => $ordenar,
            'direcao' => $direcao,
        ], 'Auditoria');
    }

    /**
     * Exporta em CSV os mesmos registros filtrados/ordenados na tela (sem
     * paginacao) - mesma logica de filtro do index(), so troca listar()
     * paginado por listarTodos().
     */
    public function exportarCsv()
    {
        $filtros = $this->filtrosDaRequisicao();
        list($ordenar, $direcao) = $this->ordenacaoDaRequisicao();
        $registros = $this->logs->listarTodos($filtros, $ordenar, $direcao);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria.csv"');

        $saida = fopen('php://output', 'w');
        fwrite($saida, "\xEF\xBB\xBF"); // BOM UTF-8, pro Excel abrir acentuacao certo.
        fputcsv($saida, ['Horário', 'Usuário', 'Ação', 'Entidade', 'ID', 'IP', 'Mensagem']);

        foreach ($registros as $registro) {
            fputcsv($saida, [
                $registro['criado_em'],
                $registro['usuario_nome'] !== null ? $registro['usuario_nome'] : 'Sistema',
                $registro['acao'],
                $registro['entidade'],
                $registro['entidade_id'],
                $registro['ip_origem'],
                $registro['mensagem'],
            ]);
        }

        fclose($saida);
        exit;
    }

    private function ordenacaoDaRequisicao()
    {
        $ordenar = isset($_GET['ordenar']) && in_array($_GET['ordenar'], self::$colunasOrdenaveis, true)
            ? $_GET['ordenar']
            : 'criado_em';
        $direcao = isset($_GET['direcao']) && $_GET['direcao'] === 'asc' ? 'asc' : 'desc';

        return [$ordenar, $direcao];
    }

    private function filtrosDaRequisicao()
    {
        return [
            'usuario_id' => (isset($_GET['usuario_id']) && $_GET['usuario_id'] !== '') ? (int) $_GET['usuario_id'] : null,
            'acao' => isset($_GET['acao']) && $_GET['acao'] !== '' ? trim($_GET['acao']) : null,
            'data_inicio' => isset($_GET['data_inicio']) && $_GET['data_inicio'] !== '' ? $_GET['data_inicio'] : null,
            'data_fim' => isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : null,
            'busca' => isset($_GET['busca']) && trim($_GET['busca']) !== '' ? trim($_GET['busca']) : null,
        ];
    }
}
