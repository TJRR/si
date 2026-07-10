<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\ExpressaoAritmetica;
use App\Repositories\CriterioAvaliacaoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormulaPontuacaoRepository;
use App\Repositories\NotaLancadaRepository;
use App\Repositories\RegraDesempateRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;

/**
 * Consolida notas lancadas pelos avaliadores (conforme o modo_consolidacao da
 * etapa), roda a formula livre (App\Core\ExpressaoAritmetica, ja existente e
 * testada) para obter a NE de cada submissao, aplica o corte configurado em
 * regra_transicao_tipo/valor e desempata usando as regras_desempate da propria
 * etapa quando ha empate na borda do corte.
 */
class ResultadoEtapaService
{
    private $etapas;
    private $criterios;
    private $formulas;
    private $submissoes;
    private $notas;
    private $regrasDesempate;
    private $resultados;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->criterios = new CriterioAvaliacaoRepository();
        $this->formulas = new FormulaPontuacaoRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->notas = new NotaLancadaRepository();
        $this->regrasDesempate = new RegraDesempateRepository();
        $this->resultados = new ResultadoEtapaRepository();
    }

    public function calcularRanking($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);
        $criterios = $this->criterios->listarPorEtapa($etapaId);
        $formula = $this->formulas->buscarPorEtapa($etapaId);

        if ($formula === null) {
            throw new \RuntimeException('Nenhuma fórmula cadastrada para esta etapa.');
        }

        $submissoesDaEtapa = $this->submissoes->listarPorEtapa($etapaId);
        $linhas = [];

        foreach ($submissoesDaEtapa as $submissao) {
            $ne = $this->calcularNe($submissao['id'], $criterios, $formula['expressao'], $etapa['modo_consolidacao']);

            $linhas[] = [
                'submissao_id' => (int) $submissao['id'],
                'equipe_id' => $submissao['equipe_id'] !== null ? (int) $submissao['equipe_id'] : null,
                'nome_equipe' => $submissao['nome_equipe'],
                'ne' => $ne,
                'classificado' => false,
            ];
        }

        $regrasDaEtapa = array_values(array_filter(
            $this->regrasDesempate->listarPorTrilha($etapa['trilha_id']),
            function ($regra) use ($etapaId) {
                $criterio = $this->criterios->buscarPorId($regra['criterio_avaliacao_id']);

                return $criterio !== null && (int) $criterio['etapa_id'] === (int) $etapaId;
            }
        ));

        usort($linhas, function ($a, $b) use ($regrasDaEtapa) {
            return $this->compararLinhas($a, $b, $regrasDaEtapa);
        });

        $this->marcarClassificados($linhas, $etapa);

        return $linhas;
    }

    public function publicar($etapaId, $usuarioId)
    {
        $ranking = $this->calcularRanking($etapaId);
        $this->resultados->publicar($etapaId, $ranking, $usuarioId);

        return $ranking;
    }

    public function reabrir($etapaId)
    {
        $this->resultados->reabrir($etapaId);
    }

    public function jaPublicado($etapaId)
    {
        return $this->resultados->jaPublicado($etapaId);
    }

    private function calcularNe($submissaoId, array $criterios, $expressao, $modoConsolidacao)
    {
        $porAvaliador = $this->notasAgrupadasPorAvaliador($submissaoId);

        if (empty($porAvaliador)) {
            return null;
        }

        if ($modoConsolidacao === 'media_ne') {
            $nes = [];

            foreach ($porAvaliador as $notasDoAvaliador) {
                $porCodigo = $this->mapearParaCodigo($notasDoAvaliador, $criterios);

                if (count($porCodigo) < count($criterios)) {
                    continue;
                }

                $nes[] = ExpressaoAritmetica::avaliar($expressao, $porCodigo);
            }

            return empty($nes) ? null : array_sum($nes) / count($nes);
        }

        $mediaPorCodigo = $this->mediaPorCriterio($porAvaliador, $criterios);

        if (count($mediaPorCodigo) < count($criterios)) {
            return null;
        }

        return ExpressaoAritmetica::avaliar($expressao, $mediaPorCodigo);
    }

    private function notasAgrupadasPorAvaliador($submissaoId)
    {
        $porAvaliador = [];

        foreach ($this->notas->listarPorSubmissao($submissaoId) as $nota) {
            $porAvaliador[(int) $nota['usuario_id']][(int) $nota['criterio_avaliacao_id']] = (float) $nota['nota'];
        }

        return $porAvaliador;
    }

    private function mapearParaCodigo(array $notasPorCriterioId, array $criterios)
    {
        $porCodigo = [];

        foreach ($criterios as $criterio) {
            if (array_key_exists((int) $criterio['id'], $notasPorCriterioId)) {
                $porCodigo[$criterio['codigo']] = $notasPorCriterioId[(int) $criterio['id']];
            }
        }

        return $porCodigo;
    }

    private function mediaPorCriterio(array $porAvaliador, array $criterios)
    {
        $porCodigo = [];

        foreach ($criterios as $criterio) {
            $valores = [];

            foreach ($porAvaliador as $notasDoAvaliador) {
                if (array_key_exists((int) $criterio['id'], $notasDoAvaliador)) {
                    $valores[] = $notasDoAvaliador[(int) $criterio['id']];
                }
            }

            if (!empty($valores)) {
                $porCodigo[$criterio['codigo']] = array_sum($valores) / count($valores);
            }
        }

        return $porCodigo;
    }

    private function valorDesempatePorSubmissao($submissaoId, $criterioId)
    {
        $valores = [];

        foreach ($this->notas->listarPorSubmissao($submissaoId) as $nota) {
            if ((int) $nota['criterio_avaliacao_id'] === (int) $criterioId) {
                $valores[] = (float) $nota['nota'];
            }
        }

        return empty($valores) ? null : array_sum($valores) / count($valores);
    }

    private function compararLinhas(array $a, array $b, array $regrasDaEtapa)
    {
        if ($a['ne'] === null && $b['ne'] === null) {
            return 0;
        }

        if ($a['ne'] === null) {
            return 1;
        }

        if ($b['ne'] === null) {
            return -1;
        }

        if ($a['ne'] != $b['ne']) {
            return $a['ne'] < $b['ne'] ? 1 : -1;
        }

        foreach ($regrasDaEtapa as $regra) {
            $valorA = $this->valorDesempatePorSubmissao($a['submissao_id'], $regra['criterio_avaliacao_id']);
            $valorB = $this->valorDesempatePorSubmissao($b['submissao_id'], $regra['criterio_avaliacao_id']);

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

    private function marcarClassificados(array &$linhas, array $etapa)
    {
        $tipo = $etapa['regra_transicao_tipo'];
        $valor = $etapa['regra_transicao_valor'] !== null ? (float) $etapa['regra_transicao_valor'] : null;
        $totalElegiveis = count(array_filter($linhas, function ($linha) {
            return $linha['ne'] !== null;
        }));

        $corte = $totalElegiveis;

        if ($tipo === 'numero_fixo') {
            $corte = (int) $valor;
        } elseif ($tipo === 'percentual') {
            $corte = (int) ceil($totalElegiveis * $valor / 100);
        }

        $posicao = 0;

        foreach ($linhas as &$linha) {
            if ($linha['ne'] === null) {
                $linha['classificado'] = false;
                continue;
            }

            if ($tipo === 'nota_corte') {
                $linha['classificado'] = $linha['ne'] >= $valor;
                continue;
            }

            $posicao++;
            $linha['classificado'] = $posicao <= $corte;
        }
        unset($linha);
    }
}
