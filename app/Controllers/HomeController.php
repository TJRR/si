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
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\TemaDesafioRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ResultadoEtapaService;

class HomeController extends Controller
{
    public function index()
    {
        $concursos = new ConcursoRepository();
        $trilhas = new TrilhaRepository();
        $etapas = new EtapaRepository();
        $temas = new TemaDesafioRepository();
        $formularios = new FormularioDinamicoRepository();
        $servicoResultadoEtapa = new ResultadoEtapaService();

        $concursoAtivo = $concursos->buscarAtivo();
        $trilhasAtivas = $concursoAtivo !== null ? $trilhas->listarPorConcurso($concursoAtivo['id']) : [];

        $cronograma = [];
        $temasPorTrilha = [];
        $trilhasComInscricaoAberta = [];
        $etapasComResultadoPublicado = [];

        foreach ($trilhasAtivas as $trilha) {
            foreach ($etapas->listarPorTrilha($trilha['id']) as $etapa) {
                $etapa['trilha_nome'] = $trilha['nome'];
                $cronograma[] = $etapa;

                if ($servicoResultadoEtapa->jaPublicado($etapa['id'])) {
                    $etapasComResultadoPublicado[] = [
                        'etapa_id' => $etapa['id'],
                        'etapa_nome' => $etapa['nome'],
                        'trilha_nome' => $trilha['nome'],
                    ];
                }

                if ((int) $etapa['ordem'] === 1 && $etapa['captura_ativa']) {
                    $formularioDaEtapa = $etapa['formulario_dinamico_id'] !== null
                        ? $formularios->buscarPorId($etapa['formulario_dinamico_id'])
                        : null;

                    if ($formularioDaEtapa !== null && $formularioDaEtapa['status'] === 'publicado') {
                        $trilhasComInscricaoAberta[] = [
                            'trilha_nome' => $trilha['nome'],
                            'etapa_id' => $etapa['id'],
                        ];
                    }
                }
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
                'trilhasComInscricaoAberta' => $trilhasComInscricaoAberta,
                'etapasComResultadoPublicado' => $etapasComResultadoPublicado,
            ],
            'Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR'
        );
    }

    public function administrativo()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);

        $concursosAtivos = array_filter(
            (new ConcursoRepository())->listar(),
            function ($concurso) {
                return $concurso['status'] === 'ativo';
            }
        );

        $this->renderizar('home/administrativo', [
            'totalEquipes' => count((new EquipeRepository())->listarComContagemParticipantes()),
            'totalCadastrosPendentes' => count((new UsuarioRepository())->listarPendentes()),
            'totalConcursosAtivos' => count($concursosAtivos),
        ], 'Painel');
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
