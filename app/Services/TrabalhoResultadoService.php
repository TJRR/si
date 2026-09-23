<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoCriterioRepository;
use App\Repositories\TrabalhoDesignacaoRepository;
use App\Repositories\TrabalhoNotaRepository;
use App\Repositories\TrabalhoRegraDesempateRepository;
use App\Repositories\TrabalhoRepository;

/**
 * Fase 49: calculo de resultado de Trabalhos - inspirado no ESTILO de
 * ResultadoEtapaService do Concurso (EPSILON de comparacao de ponto
 * flutuante, cascata de regras de desempate, corte de aprovacao/selecao
 * configuravel), mas sem nenhuma FK para etapas/concursos (decisao de
 * arquitetura numero 5). Nota final nunca e' persistida, sempre calculada
 * em tempo real a partir de trabalho_notas.
 *
 * metodo_agregacao_nota (media_aritmetica ou mediana, configuravel por
 * evento) resolve como as notas totais de cada avaliador se combinam -
 * achado corrigido na revisao desta fase (a versao anterior fixava soma
 * seguida de media aritmetica no codigo, sem generalizar, apesar do
 * proprio Concurso ja ter o precedente de modo_consolidacao configuravel).
 */
class TrabalhoResultadoService
{
    const EPSILON = 0.000001;

    private $trabalhos;
    private $designacoes;
    private $notas;
    private $criterios;
    private $regrasDesempate;
    private $config;

    public function __construct()
    {
        $this->trabalhos = new TrabalhoRepository();
        $this->designacoes = new TrabalhoDesignacaoRepository();
        $this->notas = new TrabalhoNotaRepository();
        $this->criterios = new TrabalhoCriterioRepository();
        $this->regrasDesempate = new TrabalhoRegraDesempateRepository();
        $this->config = new TrabalhoConfigRepository();
    }

    /**
     * Nota total de um trabalho: soma dos criterios lancados por cada
     * avaliador designado (que ja tenha lancado pelo menos 1 criterio),
     * agregadas entre avaliadores conforme $metodoAgregacao. Retorna null
     * se nenhum avaliador designado lancou nota ainda - nao ha trava que
     * exija todos os avaliadores terem terminado (mesmo comportamento ja
     * aceito hoje no Concurso: calcula com quem lancou).
     */
    public function calcularNotaFinal($trabalhoId, $metodoAgregacao)
    {
        $somasPorAvaliador = [];

        foreach ($this->designacoes->listarPorTrabalho($trabalhoId) as $designacao) {
            if ($this->notas->contarCriteriosNotados($designacao['id']) === 0) {
                continue;
            }

            $somasPorAvaliador[] = $this->notas->somaPorDesignacao($designacao['id']);
        }

        if (empty($somasPorAvaliador)) {
            return null;
        }

        if ($metodoAgregacao === 'mediana') {
            return $this->mediana($somasPorAvaliador);
        }

        return array_sum($somasPorAvaliador) / count($somasPorAvaliador);
    }

    private function mediana(array $valores)
    {
        sort($valores);
        $quantidade = count($valores);
        $meio = (int) floor(($quantidade - 1) / 2);

        if ($quantidade % 2 === 0) {
            return ($valores[$meio] + $valores[$meio + 1]) / 2;
        }

        return $valores[$meio];
    }

    /**
     * Lista ordenada (maior nota primeiro, desempate em cascata) de todos
     * os trabalhos nao desclassificados de um evento, com nota e situacao
     * de aprovacao calculadas. Usado tanto pela tela administrativa de
     * Resultado quanto por aplicarResultado().
     */
    public function calcularRanking($eventoId)
    {
        $config = $this->config->buscarPorEvento($eventoId);
        $metodoAgregacao = $config !== null ? $config['metodo_agregacao_nota'] : 'media_aritmetica';
        $notaCorte = ($config !== null && $config['nota_corte_aprovacao'] !== null) ? (float) $config['nota_corte_aprovacao'] : null;

        $regras = $this->regrasDesempate->listarPorEvento($eventoId);

        $linhas = [];

        foreach ($this->trabalhos->listarPorEvento($eventoId) as $trabalho) {
            if ($trabalho['status'] === 'desclassificado') {
                continue;
            }

            $nota = $this->calcularNotaFinal($trabalho['id'], $metodoAgregacao);

            $linhas[] = [
                'trabalho_id' => (int) $trabalho['id'],
                'titulo' => $trabalho['titulo'],
                'autor_principal_nome' => isset($trabalho['autor_principal_nome']) ? $trabalho['autor_principal_nome'] : null,
                'nota' => $nota,
                'aprovado' => $nota !== null && ($notaCorte === null || $nota > $notaCorte),
                'submetido_em' => $trabalho['submetido_em'],
            ];
        }

        usort($linhas, function (array $a, array $b) use ($regras) {
            return $this->compararLinhas($a, $b, $regras);
        });

        return $this->anotarDesempate($linhas, $regras);
    }

    /**
     * Fase 51: qual regra decidiu o empate com o trabalho imediatamente
     * acima (item 7.6 do edital: originalidade, relevancia, aderencia e,
     * por fim, data e hora de envio). Texto pronto, gravado no trabalho
     * quando o resultado e' aplicado.
     */
    private function anotarDesempate(array $linhas, array $regras)
    {
        foreach ($linhas as $indice => &$linha) {
            $linha['desempate_criterio'] = null;

            if ($indice === 0 || $linha['nota'] === null) {
                continue;
            }

            $acima = $linhas[$indice - 1];

            if ($acima['nota'] === null || abs($linha['nota'] - $acima['nota']) > self::EPSILON) {
                continue;
            }

            $linha['desempate_criterio'] = $this->regraDecisiva($acima, $linha, $regras);
        }

        unset($linha);

        return $linhas;
    }

    private function regraDecisiva(array $acima, array $abaixo, array $regras)
    {
        foreach ($regras as $regra) {
            if ($regra['tipo'] === 'data_submissao') {
                if ($acima['submetido_em'] === $abaixo['submetido_em']) {
                    continue;
                }

                return 'Data e hora de envio: ' . ($regra['direcao'] === 'asc' ? 'quem enviou primeiro vence' : 'quem enviou por último vence');
            }

            $valorAcima = $this->notas->mediaPorTrabalhoECriterio($acima['trabalho_id'], $regra['criterio_id']);
            $valorAbaixo = $this->notas->mediaPorTrabalhoECriterio($abaixo['trabalho_id'], $regra['criterio_id']);

            if ($valorAcima === null && $valorAbaixo === null) {
                continue;
            }

            if ($valorAcima !== null && $valorAbaixo !== null && abs($valorAcima - $valorAbaixo) <= self::EPSILON) {
                continue;
            }

            $nome = !empty($regra['criterio_nome']) ? $regra['criterio_nome'] : 'critério de desempate';
            $direcao = $regra['direcao'] === 'asc' ? 'menor nota vence' : 'maior nota vence';

            return $nome . ': ' . $direcao;
        }

        return 'Empate não resolvido por nenhuma regra cadastrada';
    }

    private function compararLinhas(array $a, array $b, array $regras)
    {
        if ($a['nota'] === null && $b['nota'] === null) {
            return 0;
        }

        if ($a['nota'] === null) {
            return 1;
        }

        if ($b['nota'] === null) {
            return -1;
        }

        if (abs($a['nota'] - $b['nota']) > self::EPSILON) {
            return $a['nota'] < $b['nota'] ? 1 : -1;
        }

        foreach ($regras as $regra) {
            if ($regra['tipo'] === 'data_submissao') {
                $valorA = $a['submetido_em'];
                $valorB = $b['submetido_em'];

                if ($valorA === $valorB) {
                    continue;
                }

                $comparacao = $valorA < $valorB ? -1 : 1;

                return $regra['direcao'] === 'asc' ? $comparacao : -$comparacao;
            }

            $valorA = $this->notas->mediaPorTrabalhoECriterio($a['trabalho_id'], $regra['criterio_id']);
            $valorB = $this->notas->mediaPorTrabalhoECriterio($b['trabalho_id'], $regra['criterio_id']);

            if ($valorA === null && $valorB === null) {
                continue;
            }

            if ($valorA !== null && $valorB !== null && abs($valorA - $valorB) <= self::EPSILON) {
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

    /**
     * Grava status aprovado/reprovado de cada trabalho e marca
     * "selecionado" nos primeiros N aprovados conforme
     * regra_selecao_tipo/regra_selecao_valor do evento (mesmo estilo de
     * ResultadoEtapaService::marcarClassificados() do Concurso, tabela
     * propria). Acao administrativa explicita (nao roda sozinha).
     */
    public function aplicarResultado($eventoId)
    {
        $config = $this->config->buscarPorEvento($eventoId);

        if ($config === null) {
            throw new \RuntimeException('Este evento ainda não tem configuração de Trabalhos.');
        }

        $linhas = $this->calcularRanking($eventoId);

        foreach ($linhas as $linha) {
            $status = $linha['aprovado'] ? 'aprovado' : 'reprovado';
            $this->trabalhos->atualizarStatus($linha['trabalho_id'], $status);
            $this->trabalhos->marcarSelecionado($linha['trabalho_id'], false);
            $this->trabalhos->definirDesempateCriterio($linha['trabalho_id'], $linha['desempate_criterio']);
        }

        $aprovados = array_values(array_filter($linhas, function (array $linha) {
            return $linha['aprovado'];
        }));

        $tipo = $config['regra_selecao_tipo'];
        $valor = $config['regra_selecao_valor'] !== null ? (float) $config['regra_selecao_valor'] : null;
        $totalAprovados = count($aprovados);

        if ($tipo === 'numero_fixo' && $valor !== null) {
            $corte = (int) $valor;
        } elseif ($tipo === 'percentual' && $valor !== null) {
            $corte = (int) ceil($totalAprovados * $valor / 100);
        } else {
            $corte = $totalAprovados;
        }

        foreach ($aprovados as $posicao => $linha) {
            if ($posicao < $corte) {
                $this->trabalhos->marcarSelecionado($linha['trabalho_id'], true);
            }
        }
    }
}
