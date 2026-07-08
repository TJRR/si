<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\ConteudoSiteRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\TemaDesafioRepository;
use App\Repositories\TrilhaRepository;

class HomeController extends Controller
{
    public function index()
    {
        $concursos = new ConcursoRepository();
        $trilhas = new TrilhaRepository();
        $etapas = new EtapaRepository();
        $temas = new TemaDesafioRepository();

        $concursoAtivo = $concursos->buscarAtivo();
        $trilhasAtivas = $concursoAtivo !== null ? $trilhas->listarPorConcurso($concursoAtivo['id']) : [];

        $cronograma = [];
        $temasPorTrilha = [];

        foreach ($trilhasAtivas as $trilha) {
            foreach ($etapas->listarPorTrilha($trilha['id']) as $etapa) {
                $etapa['trilha_nome'] = $trilha['nome'];
                $cronograma[] = $etapa;
            }

            $temasPorTrilha[] = [
                'trilha' => $trilha,
                'temas' => $temas->listarAtivosPorTrilha($trilha['id']),
            ];
        }

        usort($cronograma, function ($a, $b) {
            return strcmp((string) $a['data_inicio'], (string) $b['data_inicio']);
        });

        $this->renderizar(
            'home/index',
            [
                'conteudo' => (new ConteudoSiteRepository())->listarComoMapa(),
                'concursoAtivo' => $concursoAtivo,
                'trilhasAtivas' => $trilhasAtivas,
                'cronograma' => $cronograma,
                'temasPorTrilha' => $temasPorTrilha,
            ],
            'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR'
        );
    }

    public function administrativo()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->renderizar('home/administrativo', [], 'Painel');
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
