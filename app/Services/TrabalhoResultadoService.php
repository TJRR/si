<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\EventoAnaisRepository;
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
 * arquitetura numero 5). Ate a Fase 51 a nota final nunca era persistida.
 * Desde a Fase 52 a tela administrativa continua mostrando uma previa
 * calculada em tempo real a partir de trabalho_notas, e a PUBLICACAO do
 * resultado (publicarResultado) congela nota, posicao e media por criterio
 * no proprio trabalho: e' isso que o autor ve, e so' depois de publicado.
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
     * Resultado quanto por publicarResultado().
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
     * Fase 52: quais trabalhos ficam selecionados para apresentacao, isto
     * e, os primeiros N aprovados da lista ja ordenada, conforme
     * regra_selecao_tipo/regra_selecao_valor do evento (mesmo estilo de
     * ResultadoEtapaService::marcarClassificados() do Concurso, tabela
     * propria). Devolve a lista de ids. Usado pela previa da tela
     * administrativa e pela publicacao, para as duas mostrarem o mesmo.
     */
    public function calcularSelecao(array $linhas, array $config)
    {
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

        $selecionados = [];

        foreach ($aprovados as $posicao => $linha) {
            if ($posicao < $corte) {
                $selecionados[] = $linha['trabalho_id'];
            }
        }

        return $selecionados;
    }

    /**
     * Fase 52: calcula o resultado, grava em cada trabalho o que o autor vai
     * ver (situacao, selecao, nota final, posicao e media por criterio,
     * congelados a partir deste momento) e marca o resultado do evento como
     * publicado. Substitui o antigo "calcular e aplicar": nao existe mais um
     * estado intermediario em que o resultado esta gravado mas ainda nao foi
     * publicado.
     *
     * A linha de configuracao do evento e' travada (FOR UPDATE) ANTES de
     * conferir se ja esta publicado: duas chamadas simultaneas (duplo clique)
     * se serializam, a segunda enxerga "ja publicado" e falha sem gravar
     * nada. Quem chama so' dispara os avisos aos autores quando este metodo
     * volta sem excecao.
     */
    public function publicarResultado($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $config = $this->config->buscarPorEventoParaAtualizar($eventoId);

            if ($config === null) {
                throw new \RuntimeException('Este evento ainda não tem configuração de Trabalhos.');
            }

            if (!empty($config['resultado_publicado_em'])) {
                throw new \RuntimeException('O resultado já está publicado. Reabra o resultado para recalcular e publicar de novo.');
            }

            $linhas = $this->calcularRanking($eventoId);
            $selecionados = $this->calcularSelecao($linhas, $config);
            $criterios = $this->criterios->listarPorEvento($eventoId);
            $notaMaxima = $this->criterios->notaMaximaTotal($eventoId);

            $totalAvaliados = 0;

            foreach ($linhas as $linha) {
                if ($linha['nota'] !== null) {
                    $totalAvaliados++;
                }
            }

            foreach ($linhas as $indice => $linha) {
                $detalhe = [
                    'nota_maxima' => $notaMaxima,
                    'total_avaliados' => $totalAvaliados,
                    'criterios' => [],
                ];

                foreach ($criterios as $criterio) {
                    $media = $this->notas->mediaPorTrabalhoECriterio($linha['trabalho_id'], $criterio['id']);

                    $detalhe['criterios'][] = [
                        'nome' => $criterio['nome'],
                        'media' => $media !== null ? round($media, 2) : null,
                        'maximo' => (float) $criterio['nota_maxima'],
                    ];
                }

                $this->trabalhos->gravarResultado(
                    $linha['trabalho_id'],
                    $linha['aprovado'] ? 'aprovado' : 'reprovado',
                    in_array($linha['trabalho_id'], $selecionados, true),
                    $linha['desempate_criterio'],
                    $linha['nota'] !== null ? round($linha['nota'], 2) : null,
                    $linha['nota'] !== null ? $indice + 1 : null,
                    json_encode($detalhe)
                );
            }

            $this->trabalhos->limparResultadoDesclassificados($eventoId);
            $this->config->marcarResultadoPublicado($eventoId, $usuarioId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('publicar_resultado', 'evento_trabalhos_config', (int) $config['id'], null, [
            'evento_id' => (int) $eventoId,
            'trabalhos' => count($linhas),
            'selecionados' => count($selecionados),
            'publicado_por' => $usuarioId,
        ]);

        return true;
    }

    /**
     * Fase 52: tira o resultado do ar (o autor volta a ver "Submetido") para
     * o Admin poder recalcular e publicar de novo. Nao apaga o que ficou
     * gravado nos trabalhos; a proxima publicacao regrava tudo. Mesma trava
     * de linha de publicarResultado(), para os dois nao se cruzarem.
     */
    public function reabrirResultado($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $config = $this->config->buscarPorEventoParaAtualizar($eventoId);

            if ($config === null || empty($config['resultado_publicado_em'])) {
                throw new \RuntimeException('O resultado deste evento não está publicado.');
            }

            // Fase 53: os Anais publicados dependem do resultado (so' entram
            // trabalhos aprovados). Reabrir com eles no ar deixaria a lista
            // que os autores ja viram sem base; despublicar vem antes.
            if ((new EventoAnaisRepository())->estaPublicado($eventoId)) {
                throw new \RuntimeException('Os Anais deste evento estão publicados. Despublique os Anais (aba Anais) antes de reabrir o resultado.');
            }

            $this->config->limparResultadoPublicado($eventoId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('reabrir_resultado', 'evento_trabalhos_config', (int) $config['id'], [
            'resultado_publicado_em' => $config['resultado_publicado_em'],
            'resultado_publicado_por' => $config['resultado_publicado_por'],
        ], [
            'evento_id' => (int) $eventoId,
            'reaberto_por' => $usuarioId,
        ]);

        return true;
    }

    /**
     * Fase 52: enquanto o resultado do evento nao esta publicado, o autor
     * nunca ve aprovado ou reprovado, mesmo que o trabalho ja esteja gravado
     * assim (resultado reaberto). Desclassificado e' um ato individual e
     * continua visivel na hora.
     */
    public function situacaoVisivelParaAutor($status, array $config = null)
    {
        $publicado = $config !== null && !empty($config['resultado_publicado_em']);

        if (!$publicado && in_array($status, ['aprovado', 'reprovado'], true)) {
            return 'submetido';
        }

        return $status;
    }

    /**
     * Fase 52: o que o autor ve do resultado publicado do proprio trabalho,
     * ja formatado para a tela, ou null enquanto nao houver resultado a
     * mostrar (evento sem publicacao, trabalho ainda submetido ou
     * desclassificado). Cada bloco (nota final, posicao, media por criterio)
     * so' sai se a opcao correspondente estiver ligada na configuracao do
     * evento e o dado existir. Le so' o que foi congelado no proprio
     * trabalho: nunca a nota de um avaliador individual.
     */
    public function resultadoParaAutor(array $trabalho, array $config = null)
    {
        if ($config === null || empty($config['resultado_publicado_em'])) {
            return null;
        }

        if (!in_array($trabalho['status'], ['aprovado', 'reprovado'], true)) {
            return null;
        }

        $detalhe = !empty($trabalho['resultado_detalhe_json']) ? json_decode($trabalho['resultado_detalhe_json'], true) : null;

        if (!is_array($detalhe)) {
            $detalhe = [];
        }

        $formatar = function ($valor) {
            return number_format((float) $valor, 2, ',', '.');
        };

        $aprovado = $trabalho['status'] === 'aprovado';

        $resultado = [
            'situacao' => $aprovado ? 'Aprovado' : 'Reprovado',
            'selecionado' => $aprovado ? ((int) $trabalho['selecionado'] === 1) : null,
            'nota' => null,
            'posicao' => null,
            'criterios' => [],
        ];

        if (!empty($config['resultado_exibe_nota']) && $trabalho['nota_final'] !== null) {
            $resultado['nota'] = [
                'valor' => $formatar($trabalho['nota_final']),
                'maxima' => (isset($detalhe['nota_maxima']) && (float) $detalhe['nota_maxima'] > 0) ? $formatar($detalhe['nota_maxima']) : null,
            ];
        }

        if (!empty($config['resultado_exibe_posicao']) && $trabalho['posicao'] !== null) {
            $resultado['posicao'] = [
                'numero' => (int) $trabalho['posicao'],
                'total' => isset($detalhe['total_avaliados']) ? (int) $detalhe['total_avaliados'] : null,
            ];
        }

        if (!empty($config['resultado_exibe_criterios']) && !empty($detalhe['criterios'])) {
            foreach ($detalhe['criterios'] as $criterio) {
                $resultado['criterios'][] = [
                    'nome' => $criterio['nome'],
                    'media' => $criterio['media'] !== null ? $formatar($criterio['media']) : null,
                    'maximo' => $formatar($criterio['maximo']),
                ];
            }
        }

        return $resultado;
    }
}
