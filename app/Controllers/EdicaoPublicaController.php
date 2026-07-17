<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\ConcursoRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\MidiaRepository;
use App\Repositories\ResultadoTrilhaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;

/**
 * Fase 18 (3.11): repositorio historico publico das edicoes encerradas -
 * reaproveita Concurso/Trilha/Etapa/ResultadoTrilha/Submissao ja existentes,
 * nao recadastra nada manualmente (so' complementa com o que realmente nao
 * existia: resumo/imagem de destaque do case, ja em resultados_trilha desde
 * a migration 076, e a galeria de midia).
 */
class EdicaoPublicaController extends Controller
{
    private $concursos;
    private $trilhas;
    private $etapas;
    private $equipes;
    private $resultadosTrilha;
    private $submissoes;
    private $documentos;
    private $midias;

    public function __construct()
    {
        $this->concursos = new ConcursoRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->equipes = new EquipeRepository();
        $this->resultadosTrilha = new ResultadoTrilhaRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->documentos = new DocumentoRepository();
        $this->midias = new MidiaRepository();
    }

    public function index()
    {
        $this->renderizar('publico/edicoes/index', [
            'edicoes' => $this->concursos->listarEncerrados(),
        ], 'Edições Anteriores');
    }

    public function detalhe($slug)
    {
        $concurso = $this->concursos->buscarPorSlug($slug);

        if ($concurso === null || $concurso['status'] !== 'encerrado') {
            http_response_code(404);
            exit('Edição não encontrada.');
        }

        $trilhasDoConcurso = $this->trilhas->listarPorConcurso($concurso['id']);

        $totalEquipes = 0;
        $totalParticipantes = 0;
        $vencedoresPorTrilha = [];

        foreach ($trilhasDoConcurso as $trilha) {
            foreach ($this->equipes->listarComContagemParticipantes($trilha['id']) as $equipe) {
                $totalEquipes++;
                $totalParticipantes += (int) $equipe['total_participantes'];
            }

            $etapasDaTrilha = $this->etapas->listarPorTrilha($trilha['id']);
            $etapasDescendente = array_reverse($etapasDaTrilha);

            $vencedores = array_values(array_filter(
                $this->resultadosTrilha->listarPorTrilha($trilha['id']),
                function ($linha) {
                    return $linha['colocacao'] !== null && (int) $linha['colocacao'] <= 3;
                }
            ));

            foreach ($vencedores as &$vencedor) {
                $vencedor['youtube_id'] = null;

                foreach ($etapasDescendente as $etapa) {
                    $submissao = $this->submissoes->buscarPorEquipeEEtapa($vencedor['equipe_id'], $etapa['id']);

                    if ($submissao === null) {
                        continue;
                    }

                    $youtubeId = $this->submissoes->buscarYoutubeId($submissao['id']);

                    if ($youtubeId !== null) {
                        $vencedor['youtube_id'] = $youtubeId;
                        break;
                    }
                }
            }
            unset($vencedor);

            if (!empty($vencedores)) {
                $vencedoresPorTrilha[] = ['trilha' => $trilha, 'vencedores' => $vencedores];
            }
        }

        $galeria = array_values(array_filter($this->midias->listar('imagem'), function ($midia) use ($concurso) {
            return (int) $midia['concurso_id'] === (int) $concurso['id'];
        }));

        $this->renderizar('publico/edicoes/detalhe', [
            'concurso' => $concurso,
            'trilhas' => $trilhasDoConcurso,
            'totalEquipes' => $totalEquipes,
            'totalParticipantes' => $totalParticipantes,
            'vencedoresPorTrilha' => $vencedoresPorTrilha,
            'documentos' => $this->documentos->listarAtivosPorConcurso($concurso['id']),
            'galeria' => $galeria,
        ], $concurso['nome']);
    }
}
