<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\ExpressaoAritmetica;
use App\Repositories\AvaliadorDesignacaoRepository;
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
    /**
     * Mesmo motivo do ResultadoTrilhaService::EPSILON - duas NE "iguais"
     * calculadas por caminhos aritméticos diferentes podem chegar como
     * floats infinitesimalmente diferentes; comparar sem tolerância faz o
     * desempate nunca ser aplicado quando deveria.
     */
    const EPSILON = 0.000001;

    private $etapas;
    private $criterios;
    private $formulas;
    private $submissoes;
    private $notas;
    private $regrasDesempate;
    private $resultados;
    private $designacoes;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->criterios = new CriterioAvaliacaoRepository();
        $this->formulas = new FormulaPontuacaoRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->notas = new NotaLancadaRepository();
        $this->regrasDesempate = new RegraDesempateRepository();
        $this->resultados = new ResultadoEtapaRepository();
        $this->designacoes = new AvaliadorDesignacaoRepository();
    }

    /**
     * Usado pelo gatilho de modo_avanco='automatico' (AvaliacaoController::notar)
     * para saber se ja da' para publicar sozinho: toda submissao da etapa
     * precisa ter, de cada avaliador designado, nota lancada para todos os
     * criterios - mesma checagem que AvaliacaoController::submissoes() ja
     * faz por submissao/avaliador, so que agregada para a etapa inteira.
     */
    public function avaliacaoCompleta($etapaId)
    {
        $totalCriterios = $this->criterios->contarPorEtapa($etapaId);

        if ($totalCriterios === 0) {
            return false;
        }

        $submissoes = $this->submissoes->listarPorEtapa($etapaId);

        if (empty($submissoes)) {
            return false;
        }

        foreach ($submissoes as $submissao) {
            $designacoes = $this->designacoes->listarPorSubmissao($submissao['id']);

            if (empty($designacoes)) {
                return false;
            }

            foreach ($designacoes as $designacao) {
                $notasLancadas = $this->notas->contarNotasPorUsuario($submissao['id'], $designacao['usuario_id']);

                if ($notasLancadas < $totalCriterios) {
                    return false;
                }
            }
        }

        return true;
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
                'criado_em' => $submissao['criado_em'],
                'ne' => $ne,
                'classificado' => false,
            ];
        }

        $regrasDaEtapa = $this->regrasDesempate->listarPorEtapa($etapaId);

        usort($linhas, function ($a, $b) use ($regrasDaEtapa) {
            return $this->compararLinhas($a, $b, $regrasDaEtapa);
        });

        $this->marcarClassificados($linhas, $etapa);

        return $this->anotarDesempate($linhas, $regrasDaEtapa);
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

    /**
     * Fase 27 (#2): media simples por criterio (chave = criterio_avaliacao_id,
     * nao o codigo interno da formula) - reaproveita mediaPorCriterio() ja
     * usada no calculo oficial da NE, pra tela "Notas e Feedback" do
     * participante mostrar a mesma media que realmente entra na formula, sem
     * duplicar a logica de consolidacao.
     */
    public function mediaPorCriterioId($submissaoId, array $criterios)
    {
        $porAvaliador = $this->notasAgrupadasPorAvaliador($submissaoId);
        $porCodigo = $this->mediaPorCriterio($porAvaliador, $criterios);

        $porCriterioId = [];

        foreach ($criterios as $criterio) {
            if (isset($porCodigo[$criterio['codigo']])) {
                $porCriterioId[(int) $criterio['id']] = $porCodigo[$criterio['codigo']];
            }
        }

        return $porCriterioId;
    }

    public function calcularNe($submissaoId, array $criterios, $expressao, $modoConsolidacao)
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

    /**
     * Fase 51: mesma ideia aplicada ao resultado da trilha - diz qual regra
     * decidiu o empate com a submissao imediatamente acima, em texto pronto,
     * gravado junto com o resultado publicado (que fica congelado).
     */
    private function anotarDesempate(array $linhas, array $regrasDaEtapa)
    {
        foreach ($linhas as $indice => &$linha) {
            $linha['desempate_criterio'] = null;

            if ($indice === 0 || $linha['ne'] === null) {
                continue;
            }

            $acima = $linhas[$indice - 1];

            if ($acima['ne'] === null || abs($linha['ne'] - $acima['ne']) > self::EPSILON) {
                continue;
            }

            $linha['desempate_criterio'] = $this->regraDecisiva($acima, $linha, $regrasDaEtapa);
        }

        unset($linha);

        return $linhas;
    }

    private function regraDecisiva(array $acima, array $abaixo, array $regrasDaEtapa)
    {
        foreach ($regrasDaEtapa as $regra) {
            if ($regra['tipo'] === 'data_submissao') {
                if ($acima['criado_em'] === $abaixo['criado_em']) {
                    continue;
                }

                return 'Data de inscrição: ' . ($regra['direcao'] === 'asc' ? 'quem enviou primeiro vence' : 'quem enviou por último vence');
            }

            $valorAcima = $this->valorDesempatePorSubmissao($acima['submissao_id'], $regra['criterio_avaliacao_id']);
            $valorAbaixo = $this->valorDesempatePorSubmissao($abaixo['submissao_id'], $regra['criterio_avaliacao_id']);

            if ($valorAcima === null && $valorAbaixo === null) {
                continue;
            }

            if ($valorAcima !== null && $valorAbaixo !== null && abs($valorAcima - $valorAbaixo) <= self::EPSILON) {
                continue;
            }

            $nome = !empty($regra['criterio_nome']) ? $regra['criterio_nome'] : 'critério de desempate';
            $direcao = $regra['direcao'] === 'asc' ? 'menor valor vence' : 'maior valor vence';

            return $nome . ': ' . $direcao;
        }

        return 'Empate não resolvido por nenhuma regra cadastrada';
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

        if (abs($a['ne'] - $b['ne']) > self::EPSILON) {
            return $a['ne'] < $b['ne'] ? 1 : -1;
        }

        foreach ($regrasDaEtapa as $regra) {
            if ($regra['tipo'] === 'data_submissao') {
                $valorA = $a['criado_em'];
                $valorB = $b['criado_em'];

                if ($valorA === $valorB) {
                    continue;
                }
            } else {
                $valorA = $this->valorDesempatePorSubmissao($a['submissao_id'], $regra['criterio_avaliacao_id']);
                $valorB = $this->valorDesempatePorSubmissao($b['submissao_id'], $regra['criterio_avaliacao_id']);

                if ($valorA === null && $valorB === null) {
                    continue;
                }

                if ($valorA !== null && $valorB !== null && abs($valorA - $valorB) <= self::EPSILON) {
                    continue;
                }
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
