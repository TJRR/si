<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\GamificacaoClassificacaoRepository;
use App\Repositories\GamificacaoConfigRepository;
use App\Repositories\GamificacaoDesempateRepository;

/**
 * Fase 58: encerramento da gincana e classificacao geral do evento.
 *
 * encerrada() e' a UNICA conferencia de encerramento do sistema: todo
 * ponto que credita, anula ou devolve pontos (estandes, conexoes,
 * divulgacao, bonus, presenca, competicoes) pergunta a ela antes de mexer.
 * E' isso que garante o congelamento; o filtro de instante da consulta da
 * classificacao e' so' uma segunda camada.
 */
class GamificacaoService
{
    /**
     * Criterios de desempate que o sistema sabe aplicar. O Administrador
     * escolhe quais usar e em que ordem (evento_gamificacao_desempate); a
     * direcao de cada um e' fixa, porque so' uma delas faz sentido.
     */
    const CRITERIOS_DESEMPATE = [
        'chegou_primeiro' => 'Quem chegou primeiro à pontuação (o último ponto válido mais antigo vence)',
        'pontos_presenca' => 'Mais pontos de presença em atividades',
        'pontos_competicoes' => 'Mais pontos de competições',
        'pontos_conexoes' => 'Mais pontos de conexões',
        'pontos_estandes' => 'Mais pontos de visitas a estandes',
        'pontos_divulgacao' => 'Mais pontos de divulgação',
        'pontos_bonus' => 'Mais pontos de bônus',
        'atividades_com_presenca' => 'Mais atividades diferentes com presença',
        'inscricao_mais_antiga' => 'Inscrição mais antiga no evento',
    ];

    /**
     * Resultado de encerrada() por evento, dentro da mesma requisicao: a
     * leitura de codigo e a apuracao de bonus perguntam varias vezes.
     */
    private static $encerramentoPorEvento = [];

    public static function rotuloDoCriterio($criterio)
    {
        return isset(self::CRITERIOS_DESEMPATE[$criterio]) ? self::CRITERIOS_DESEMPATE[$criterio] : $criterio;
    }

    /**
     * true quando o instante de encerramento gravado ja' chegou. Leitura
     * protegida por GamificacaoConfigRepository::vigente(): sem tabela ou
     * sem linha, a gincana nao esta' encerrada.
     */
    public static function encerrada($eventoId)
    {
        return self::encerramentoEfetivo($eventoId) !== null;
    }

    /**
     * O instante do encerramento quando ele ja' chegou, ou null. E' o valor
     * que a consulta da classificacao usa para congelar.
     */
    public static function encerramentoEfetivo($eventoId)
    {
        $eventoId = (int) $eventoId;

        if (!array_key_exists($eventoId, self::$encerramentoPorEvento)) {
            $config = (new GamificacaoConfigRepository())->vigente($eventoId);
            $instante = $config['encerramento_em'];

            self::$encerramentoPorEvento[$eventoId] = $instante !== null && strtotime($instante) <= time() ? $instante : null;
        }

        return self::$encerramentoPorEvento[$eventoId];
    }

    /**
     * Esquece o resultado guardado, depois que o Administrador altera o
     * encerramento na mesma requisicao.
     */
    public static function esquecerEncerramento($eventoId)
    {
        unset(self::$encerramentoPorEvento[(int) $eventoId]);
    }

    /**
     * Classificacao completa do evento, ja' ordenada, na forma de lista de
     * ['posicao', 'empatado', 'inscricao_id', 'nome', 'email', 'total',
     *  'por_origem', 'ultimo_ponto_em', 'inscrito_em', 'atividades'].
     * So' entra quem tem pelo menos um ponto.
     *
     * Ordem: total decrescente, depois a cascata do Administrador. Quem
     * empata em tudo divide a posicao (1, 1, 3), e a lista marca o empate.
     * Dentro de um empate, a ordem de exibicao e' alfabetica, so' para nao
     * mudar a cada leitura.
     *
     * Excecao de banco sobe: quem chama mostra "classificacao indisponivel".
     */
    public function classificacao($eventoId)
    {
        $repositorio = new GamificacaoClassificacaoRepository();
        $encerramento = self::encerramentoEfetivo($eventoId);
        $criterios = [];

        foreach ((new GamificacaoDesempateRepository())->listarCriterios($eventoId) as $linha) {
            if (isset(self::CRITERIOS_DESEMPATE[$linha['criterio']])) {
                $criterios[] = $linha['criterio'];
            }
        }

        $somas = $repositorio->somas($eventoId, $encerramento);
        $inscricoes = $repositorio->inscricoesDoEvento($eventoId);
        $atividades = in_array('atividades_com_presenca', $criterios, true)
            ? $repositorio->atividadesComPresenca($eventoId, $encerramento)
            : [];

        $linhas = [];

        foreach ($somas as $inscricaoId => $soma) {
            if ($soma['total'] <= 0 || !isset($inscricoes[$inscricaoId])) {
                continue;
            }

            $linhas[] = [
                'inscricao_id' => $inscricaoId,
                'usuario_id' => $inscricoes[$inscricaoId]['usuario_id'],
                'nome' => $inscricoes[$inscricaoId]['nome'],
                'email' => $inscricoes[$inscricaoId]['email'],
                'inscrito_em' => $inscricoes[$inscricaoId]['inscrito_em'],
                'total' => $soma['total'],
                'por_origem' => $soma['por_origem'],
                'ultimo_ponto_em' => $soma['ultimo_ponto_em'],
                'atividades' => isset($atividades[$inscricaoId]) ? $atividades[$inscricaoId] : 0,
            ];
        }

        $comparar = function ($a, $b) use ($criterios) {
            return $this->compararParaClassificacao($a, $b, $criterios);
        };

        usort($linhas, function ($a, $b) use ($comparar) {
            $resultado = $comparar($a, $b);

            if ($resultado !== 0) {
                return $resultado;
            }

            return strcasecmp($a['nome'], $b['nome']);
        });

        $posicao = 0;

        foreach ($linhas as $indice => $linha) {
            if ($indice === 0 || $comparar($linhas[$indice - 1], $linha) !== 0) {
                $posicao = $indice + 1;
                $linhas[$indice]['empatado'] = false;
            } else {
                $linhas[$indice]['empatado'] = true;
                $linhas[$indice - 1]['empatado'] = true;
            }

            $linhas[$indice]['posicao'] = $posicao;
        }

        return $linhas;
    }

    /**
     * Linha da propria pessoa dentro da classificacao, ou null quando ela
     * ainda nao tem ponto.
     */
    public static function linhaDaInscricao(array $classificacao, $inscricaoId)
    {
        foreach ($classificacao as $linha) {
            if ((int) $linha['inscricao_id'] === (int) $inscricaoId) {
                return $linha;
            }
        }

        return null;
    }

    /**
     * Total e pontos por origem de UMA pessoa, para o painel, sem ler o
     * evento inteiro. Excecao de banco sobe.
     */
    public function somaDaInscricao($eventoId, $inscricaoId)
    {
        $somas = (new GamificacaoClassificacaoRepository())->somas($eventoId, self::encerramentoEfetivo($eventoId), $inscricaoId);

        if (!isset($somas[(int) $inscricaoId])) {
            return ['total' => 0, 'por_origem' => array_fill_keys(GamificacaoClassificacaoRepository::ORIGENS, 0), 'ultimo_ponto_em' => null];
        }

        return $somas[(int) $inscricaoId];
    }

    /**
     * Negativo quando $a fica na frente. Total decrescente e depois a
     * cascata; zero significa empate em tudo.
     */
    private function compararParaClassificacao(array $a, array $b, array $criterios)
    {
        if ($a['total'] !== $b['total']) {
            return $b['total'] - $a['total'];
        }

        foreach ($criterios as $criterio) {
            $resultado = $this->compararPorCriterio($a, $b, $criterio);

            if ($resultado !== 0) {
                return $resultado;
            }
        }

        return 0;
    }

    private function compararPorCriterio(array $a, array $b, $criterio)
    {
        switch ($criterio) {
            case 'chegou_primeiro':
                return strcmp((string) $a['ultimo_ponto_em'], (string) $b['ultimo_ponto_em']);

            case 'pontos_presenca':
            case 'pontos_competicoes':
            case 'pontos_conexoes':
            case 'pontos_estandes':
            case 'pontos_divulgacao':
            case 'pontos_bonus':
                $origem = substr($criterio, strlen('pontos_'));

                return $b['por_origem'][$origem] - $a['por_origem'][$origem];

            case 'atividades_com_presenca':
                return $b['atividades'] - $a['atividades'];

            case 'inscricao_mais_antiga':
                $resultado = strcmp((string) $a['inscrito_em'], (string) $b['inscrito_em']);

                return $resultado !== 0 ? $resultado : $a['inscricao_id'] - $b['inscricao_id'];

            default:
                return 0;
        }
    }
}
