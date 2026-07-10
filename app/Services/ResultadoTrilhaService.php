<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\ExpressaoAritmetica;
use App\Repositories\CriterioAvaliacaoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormulaPontuacaoRepository;
use App\Repositories\NotaLancadaRepository;
use App\Repositories\RegraDesempateRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\ResultadoTrilhaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;

/**
 * Calcula a Nota Final (NF) por equipe combinando as NE ja publicadas
 * (resultados_etapa) de cada etapa da trilha via a formula livre cadastrada
 * para a trilha (variaveis NE<ordem>), e a colocacao final usando TODAS as
 * regras_desempate da trilha (que podem misturar criterios de etapas
 * diferentes, como e o caso real do Edital 13/2026).
 */
class ResultadoTrilhaService
{
    private $trilhas;
    private $etapas;
    private $criterios;
    private $formulas;
    private $submissoes;
    private $notas;
    private $regrasDesempate;
    private $resultadosEtapa;
    private $resultadosTrilha;
    private $equipes;

    public function __construct()
    {
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->criterios = new CriterioAvaliacaoRepository();
        $this->formulas = new FormulaPontuacaoRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->notas = new NotaLancadaRepository();
        $this->regrasDesempate = new RegraDesempateRepository();
        $this->resultadosEtapa = new ResultadoEtapaRepository();
        $this->resultadosTrilha = new ResultadoTrilhaRepository();
        $this->equipes = new EquipeRepository();
    }

    public function calcularRanking($trilhaId)
    {
        $formula = $this->formulas->buscarPorTrilha($trilhaId);

        if ($formula === null) {
            throw new \RuntimeException('Nenhuma fórmula de nota final cadastrada para esta trilha.');
        }

        $etapasDaTrilha = $this->etapas->listarPorTrilha($trilhaId);
        $neParaEquipePorEtapa = [];
        $equipeIds = [];

        foreach ($etapasDaTrilha as $etapa) {
            $variavel = 'NE' . (int) $etapa['ordem'];
            $neParaEquipePorEtapa[$variavel] = [];

            foreach ($this->resultadosEtapa->listarPorEtapa($etapa['id']) as $resultado) {
                if ($resultado['equipe_id'] === null) {
                    continue;
                }

                $neParaEquipePorEtapa[$variavel][(int) $resultado['equipe_id']] = (float) $resultado['ne'];
                $equipeIds[(int) $resultado['equipe_id']] = true;
            }
        }

        $linhas = [];

        foreach (array_keys($equipeIds) as $equipeId) {
            $variaveis = [];
            $completo = true;

            foreach ($neParaEquipePorEtapa as $variavel => $porEquipe) {
                if (!array_key_exists($equipeId, $porEquipe)) {
                    $completo = false;
                    break;
                }
                $variaveis[$variavel] = $porEquipe[$equipeId];
            }

            if (!$completo) {
                continue;
            }

            $equipe = $this->equipes->buscarPorId($equipeId);

            $linhas[] = [
                'equipe_id' => $equipeId,
                'nome_equipe' => $equipe !== null ? $equipe['nome_equipe'] : null,
                'nf' => ExpressaoAritmetica::avaliar($formula['expressao'], $variaveis),
                'colocacao' => null,
            ];
        }

        $regrasDaTrilha = $this->regrasDesempate->listarPorTrilha($trilhaId);

        usort($linhas, function ($a, $b) use ($regrasDaTrilha) {
            return $this->compararLinhas($a, $b, $regrasDaTrilha);
        });

        foreach ($linhas as $posicao => &$linha) {
            $linha['colocacao'] = $posicao + 1;
        }
        unset($linha);

        return $linhas;
    }

    public function publicar($trilhaId, $usuarioId)
    {
        $ranking = $this->calcularRanking($trilhaId);
        $this->resultadosTrilha->publicar($trilhaId, $ranking, $usuarioId);

        return $ranking;
    }

    public function reabrir($trilhaId)
    {
        $this->resultadosTrilha->reabrir($trilhaId);
    }

    public function jaPublicado($trilhaId)
    {
        return $this->resultadosTrilha->jaPublicado($trilhaId);
    }

    private function valorDesempatePorEquipe($equipeId, $criterioId)
    {
        $criterio = $this->criterios->buscarPorId($criterioId);

        if ($criterio === null) {
            return null;
        }

        $submissao = $this->submissoes->buscarPorEquipeEEtapa($equipeId, $criterio['etapa_id']);

        if ($submissao === null) {
            return null;
        }

        $valores = [];

        foreach ($this->notas->listarPorSubmissao($submissao['id']) as $nota) {
            if ((int) $nota['criterio_avaliacao_id'] === (int) $criterioId) {
                $valores[] = (float) $nota['nota'];
            }
        }

        return empty($valores) ? null : array_sum($valores) / count($valores);
    }

    private function compararLinhas(array $a, array $b, array $regrasDaTrilha)
    {
        if ($a['nf'] != $b['nf']) {
            return $a['nf'] < $b['nf'] ? 1 : -1;
        }

        foreach ($regrasDaTrilha as $regra) {
            $valorA = $this->valorDesempatePorEquipe($a['equipe_id'], $regra['criterio_avaliacao_id']);
            $valorB = $this->valorDesempatePorEquipe($b['equipe_id'], $regra['criterio_avaliacao_id']);

            if ($valorA === $valorB) {
                continue;
            }

            if ($valorA === null) {
                return 1;
            }

            if ($valorB === null) {
                return -1;
            }

            $comparacao = $valorA < $valorB ? -1 : 1;

            return $regra['direcao'] === 'asc' ? $comparacao : -$comparacao;
        }

        return 0;
    }
}
