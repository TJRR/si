<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\EtapaRepository;
use App\Repositories\FormulaPontuacaoRepository;
use App\Repositories\ResultadoTrilhaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Services\ConteudoSubmissaoService;
use App\Services\ResultadoEtapaService;
use App\Services\ResultadoTrilhaService;

/**
 * Exposicao publica (sem login) do resultado de uma etapa ja publicada pelo
 * Admin (ResultadoAdminController::publicarEtapa). O que aparece depende do
 * campo etapas.visibilidade_publica, escolhido pelo Admin (Fase 23, item C2)
 * - antes era fixo no codigo (so' nome + video, nunca nota/ranking), decisao
 * que fazia sentido so' para uma etapa de apresentacao em video e nao para
 * uma etapa de submissao de ideia (sem esse campo).
 */
class ResultadoPublicoController extends Controller
{
    private $etapas;
    private $resultados;
    private $submissoes;
    private $servicoEtapa;
    private $conteudoSubmissao;
    private $formulas;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->resultados = new ResultadoEtapaRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->servicoEtapa = new ResultadoEtapaService();
        $this->conteudoSubmissao = new ConteudoSubmissaoService();
        $this->formulas = new FormulaPontuacaoRepository();
    }

    public function etapa($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null || $etapa['visibilidade_publica'] === 'oculto' || !$this->servicoEtapa->jaPublicado($etapaId)) {
            http_response_code(404);
            exit('Resultado nao encontrado ou ainda nao publicado.');
        }

        $modo = $etapa['visibilidade_publica'];
        $linhas = $this->resultados->listarPorEtapa($etapaId);

        if ($modo === 'apenas_classificados') {
            $linhas = array_values(array_filter($linhas, function ($linha) {
                return (int) $linha['classificado'] === 1;
            }));
        }

        $posicao = 0;
        $equipes = array_map(function ($linha) use ($modo, &$posicao) {
            $posicao++;
            $equipe = [
                'nome_equipe' => $linha['nome_equipe'] !== null ? $linha['nome_equipe'] : 'Equipe #' . $linha['equipe_id'],
                'classificado' => (int) $linha['classificado'] === 1,
                'posicao' => $posicao,
                'ne' => $modo !== 'apenas_classificados' ? $linha['ne'] : null,
                'material' => null,
            ];

            if ($modo === 'ranking_e_material') {
                $equipe['material'] = $this->materialPublicoDaSubmissao((int) $linha['submissao_id']);
            }

            return $equipe;
        }, $linhas);

        $this->renderizar('publico/resultado_etapa', [
            'etapa' => $etapa,
            'modo' => $modo,
            'equipes' => $equipes,
            'casasDecimais' => FormulaPontuacaoRepository::casasDecimais($this->formulas->buscarPorEtapa($etapaId)),
        ], 'Resultado: ' . $etapa['nome']);
    }

    /**
     * Fase 51: resultado FINAL da trilha (Nota Final e colocacao), publico.
     * Ate aqui so existia resultado publico por etapa, e o "Destaque
     * publico" (resumo e imagem do case, cadastrado desde a Fase 18) nunca
     * aparecia em tela nenhuma: a funcionalidade existia pela metade.
     *
     * Tres modos, no mesmo espirito do que ja valia por etapa:
     * oculto (padrao), apenas_destaques (so as colocacoes com destaque
     * cadastrado) e ranking_completo (todas, com Nota Final).
     */
    public function trilha($trilhaId)
    {
        $trilha = (new TrilhaRepository())->buscarPorId($trilhaId);
        $servicoTrilha = new ResultadoTrilhaService();

        if ($trilha === null
            || !isset($trilha['visibilidade_publica_resultado'])
            || $trilha['visibilidade_publica_resultado'] === 'oculto'
            || !$servicoTrilha->jaPublicado($trilhaId)) {
            http_response_code(404);
            exit('Resultado nao encontrado ou ainda nao publicado.');
        }

        $modo = $trilha['visibilidade_publica_resultado'];
        $linhas = (new ResultadoTrilhaRepository())->listarPorTrilha($trilhaId);

        if ($modo === 'apenas_destaques') {
            $linhas = array_values(array_filter($linhas, function ($linha) {
                return trim((string) $linha['resumo_destaque']) !== '' || !empty($linha['imagem_destaque_path']);
            }));
        }

        $equipes = array_map(function ($linha) use ($modo) {
            return [
                'colocacao' => (int) $linha['colocacao'],
                'nome_equipe' => $linha['nome_equipe'] !== null ? $linha['nome_equipe'] : 'Equipe #' . $linha['equipe_id'],
                'nf' => $modo === 'ranking_completo' ? $linha['nf'] : null,
                'resumo_destaque' => $linha['resumo_destaque'],
                'imagem_destaque_path' => $linha['imagem_destaque_path'],
                'imagem_destaque_alt' => $linha['imagem_destaque_alt'],
            ];
        }, $linhas);

        $this->renderizar('publico/resultado_trilha', [
            'trilha' => $trilha,
            'modo' => $modo,
            'equipes' => $equipes,
            'casasDecimais' => FormulaPontuacaoRepository::casasDecimais($this->formulas->buscarPorTrilha($trilhaId)),
        ], 'Resultado final: ' . $trilha['nome']);
    }

    private function materialPublicoDaSubmissao($submissaoId)
    {
        $submissao = $this->submissoes->buscarPorId($submissaoId);

        if ($submissao === null) {
            return [];
        }

        return array_values(array_filter(
            $this->conteudoSubmissao->montar($submissao),
            function ($item) {
                return !in_array($item['campo']['tipo'], ConteudoSubmissaoService::TIPOS_CAMPO_SENSIVEIS, true);
            }
        ));
    }
}
