<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\EtapaRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Services\ResultadoEtapaService;

/**
 * Exposicao publica (sem login) do resultado de uma etapa ja publicada pelo
 * Admin (ResultadoAdminController::publicarEtapa) - por transparencia, so
 * mostra nome da equipe classificada + video (se a etapa tiver campo do tipo
 * link_youtube no formulario), nunca nota nem posicao no ranking.
 */
class ResultadoPublicoController extends Controller
{
    private $etapas;
    private $resultados;
    private $submissoes;
    private $servicoEtapa;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->resultados = new ResultadoEtapaRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->servicoEtapa = new ResultadoEtapaService();
    }

    public function etapa($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null || !$this->servicoEtapa->jaPublicado($etapaId)) {
            http_response_code(404);
            exit('Resultado nao encontrado ou ainda nao publicado.');
        }

        $classificadas = array_values(array_filter(
            $this->resultados->listarPorEtapa($etapaId),
            function ($linha) {
                return (int) $linha['classificado'] === 1;
            }
        ));

        $equipes = array_map(function ($linha) {
            return [
                'nome_equipe' => $linha['nome_equipe'] !== null ? $linha['nome_equipe'] : 'Equipe #' . $linha['equipe_id'],
                'youtube_id' => $this->submissoes->buscarYoutubeId((int) $linha['submissao_id']),
            ];
        }, $classificadas);

        $this->renderizar('publico/resultado_etapa', [
            'etapa' => $etapa,
            'equipes' => $equipes,
        ], 'Resultado — ' . $etapa['nome']);
    }
}
