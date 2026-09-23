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
    /**
     * Duas NF/notas de desempate "iguais" na fórmula (ex.: 9.08*0.4+8.56*0.6
     * vs 9.02*0.4+8.6*0.6, ambas 8,768 exibidas) podem chegar como floats
     * infinitesimalmente diferentes (8.768 vs 8.767999999999999) por erro de
     * arredondamento binário do PHP - comparar com != /=== sem tolerância
     * faz o sistema nunca considerar o empate e nunca aplicar o desempate
     * oficial do edital, decidindo a colocação por ruído de ponto flutuante.
     * Achado real em 16/09/2026 simulando um empate proposital para teste.
     */
    const EPSILON = 0.000001;

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

        $variaveisUsadas = ExpressaoAritmetica::variaveisUsadas($formula['expressao']);
        $etapasDaTrilha = $this->etapas->listarPorTrilha($trilhaId);
        $neParaEquipePorEtapa = [];
        $equipeIds = [];

        foreach ($etapasDaTrilha as $etapa) {
            $variavel = 'NE' . (int) $etapa['ordem'];

            if (!in_array($variavel, $variaveisUsadas, true)) {
                // Etapa fora da formula (ex.: "Cadastro das Equipes") - nunca
                // exigida como pre-requisito pra equipe entrar na Nota Final.
                continue;
            }

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

        return $this->anotarDesempate($linhas, $regrasDaTrilha);
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

    /**
     * Fase 51: diz, linha a linha, qual regra decidiu o empate com a equipe
     * imediatamente acima. So' preenche quando as duas Notas Finais empatam
     * dentro da mesma tolerancia usada na comparacao; quando nenhuma regra
     * separa as duas, registra isso explicitamente, em vez de deixar o Admin
     * sem resposta.
     *
     * O texto e' montado aqui e gravado assim, pronto, no momento da
     * publicacao: resultado publicado nao muda depois, nem se a regra for
     * renomeada ou removida.
     */
    private function anotarDesempate(array $linhas, array $regrasDaTrilha)
    {
        foreach ($linhas as $indice => &$linha) {
            $linha['desempate_criterio'] = null;

            if ($indice === 0) {
                continue;
            }

            $acima = $linhas[$indice - 1];

            if (abs($linha['nf'] - $acima['nf']) > self::EPSILON) {
                continue;
            }

            $linha['desempate_criterio'] = $this->regraDecisiva($acima, $linha, $regrasDaTrilha);
        }

        unset($linha);

        return $linhas;
    }

    private function regraDecisiva(array $acima, array $abaixo, array $regrasDaTrilha)
    {
        foreach ($regrasDaTrilha as $regra) {
            $valorAcima = $this->valorDesempatePorEquipe($acima['equipe_id'], $regra['criterio_avaliacao_id']);
            $valorAbaixo = $this->valorDesempatePorEquipe($abaixo['equipe_id'], $regra['criterio_avaliacao_id']);

            if ($valorAcima === null && $valorAbaixo === null) {
                continue;
            }

            if ($valorAcima === null || $valorAbaixo === null || abs($valorAcima - $valorAbaixo) > self::EPSILON) {
                $nome = !empty($regra['criterio_nome'])
                    ? $regra['criterio_nome']
                    : (isset($regra['tipo']) && $regra['tipo'] === 'data_submissao' ? 'Data de inscrição' : 'critério de desempate');
                $etapa = !empty($regra['etapa_nome']) ? ' (' . $regra['etapa_nome'] . ')' : '';
                $direcao = $regra['direcao'] === 'asc' ? 'menor valor vence' : 'maior valor vence';

                return $nome . $etapa . ': ' . $direcao;
            }
        }

        return 'Empate não resolvido por nenhuma regra cadastrada';
    }

    private function compararLinhas(array $a, array $b, array $regrasDaTrilha)
    {
        if (abs($a['nf'] - $b['nf']) > self::EPSILON) {
            return $a['nf'] < $b['nf'] ? 1 : -1;
        }

        foreach ($regrasDaTrilha as $regra) {
            $valorA = $this->valorDesempatePorEquipe($a['equipe_id'], $regra['criterio_avaliacao_id']);
            $valorB = $this->valorDesempatePorEquipe($b['equipe_id'], $regra['criterio_avaliacao_id']);

            if ($valorA === null && $valorB === null) {
                continue;
            }

            if ($valorA === null) {
                return 1;
            }

            if ($valorB === null) {
                return -1;
            }

            if (abs($valorA - $valorB) <= self::EPSILON) {
                continue;
            }

            $comparacao = $valorA < $valorB ? -1 : 1;

            return $regra['direcao'] === 'asc' ? $comparacao : -$comparacao;
        }

        return 0;
    }
}
