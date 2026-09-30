<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 57: agregacoes de presenca para a apuracao de bonus. So' LE
 * evento_checkins; nada aqui grava nem altera aquela tabela.
 *
 * Arquivo novo, e nao metodos acrescentados a EventoCheckinRepository, pelo
 * precedente de isolamento: aquele repositorio e' codigo ativo na tela de
 * presencas da atividade e nas duas exportacoes EJURR, e ate' esta fase nao
 * existia nenhum agrupamento sobre evento_checkins em todo o projeto. A
 * duplicacao proposital esta' registrada na pendencia 25.
 *
 * O DIA que conta e' DATE(a.data_inicio), o dia da ATIVIDADE, e nao o
 * instante do check-in: a confirmacao de presenca abre ate' 60 minutos antes
 * (evento_atividades.antecedencia_abertura_presenca), entao a leitura pode
 * cair no dia anterior sem que a pessoa tenha vindo naquele dia. Efeito
 * colateral assumido: atividade que atravessa a meia noite conta so' o dia
 * em que comeca.
 *
 * Toda consulta daqui filtra removido_em IS NULL: presenca marcada como
 * removida pelo Administrador (bloco E, pendencia 33) sai da contagem dos
 * bonus, e e' isso que faz a anulacao automatica do credito acontecer.
 *
 * Contar atividades distintas como COUNT(*) so' e' correto por causa da
 * chave UNIQUE (atividade_id, evento_inscricao_id) de evento_checkins
 * (migration 138): uma pessoa tem no maximo um check-in por atividade. Se
 * um dia alguem derrubar essa chave, a contagem dobra em silencio e os
 * bonus de atividades fecham com metade do que deveriam.
 */
class EventoCheckinResumoRepository
{
    /**
     * Atividades distintas e dias distintos de UMA pessoa no evento, numa
     * consulta so'. Busca por indice: evento_inscricao_id tem indice proprio,
     * criado pelo MySQL para atender a' chave estrangeira da 138.
     *
     * Consultado pelo painel do participante, entao falha de banco devolve
     * zeros em vez de derrubar a tela de todo inscrito.
     */
    public function resumoDaInscricao($eventoId, $inscricaoId)
    {
        $vazio = ['atividades' => 0, 'dias' => 0];

        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS atividades, COUNT(DISTINCT DATE(a.data_inicio)) AS dias
                   FROM evento_checkins c
                   INNER JOIN evento_atividades a ON a.id = c.atividade_id
                  WHERE c.evento_inscricao_id = :inscricao AND a.evento_id = :evento
                    AND c.removido_em IS NULL'
            );
            $stmt->execute(['inscricao' => (int) $inscricaoId, 'evento' => (int) $eventoId]);
            $linha = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao resumir as presencas da inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return $vazio;
        }

        if ($linha === false) {
            return $vazio;
        }

        return [
            'atividades' => (int) $linha['atividades'],
            'dias' => (int) $linha['dias'],
        ];
    }

    /**
     * Presencas de UMA pessoa agrupadas por tipo de atividade, na forma
     * [tipo_id => quantas]. Atividade sem tipo cadastrado nao entra, porque
     * nenhum bonus por tipo poderia alcanca-la.
     *
     * So' e' chamado quando existe bonus do tipo atividades_do_tipo no
     * evento: sem ele, a consulta seria trabalho jogado fora a cada leitura
     * de codigo.
     */
    public function porTipoDaInscricao($eventoId, $inscricaoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT a.tipo_id, COUNT(*) AS total
                   FROM evento_checkins c
                   INNER JOIN evento_atividades a ON a.id = c.atividade_id
                  WHERE c.evento_inscricao_id = :inscricao AND a.evento_id = :evento
                    AND a.tipo_id IS NOT NULL AND c.removido_em IS NULL
                  GROUP BY a.tipo_id'
            );
            $stmt->execute(['inscricao' => (int) $inscricaoId, 'evento' => (int) $eventoId]);
            $linhas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao contar presencas por tipo da inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return [];
        }

        $porTipo = [];

        foreach ($linhas as $linha) {
            $porTipo[(int) $linha['tipo_id']] = (int) $linha['total'];
        }

        return $porTipo;
    }

    /**
     * A mesma agregacao para TODOS os inscritos do evento de uma vez, na
     * forma [evento_inscricao_id => ['atividades' => n, 'dias' => n]]. Quem
     * nao tem nenhuma presenca nao aparece, entao a reapuracao percorre so'
     * quem pode ganhar alguma coisa.
     */
    public function resumoDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.evento_inscricao_id, COUNT(*) AS atividades, COUNT(DISTINCT DATE(a.data_inicio)) AS dias
               FROM evento_checkins c
               INNER JOIN evento_atividades a ON a.id = c.atividade_id
              WHERE a.evento_id = :evento AND c.removido_em IS NULL
              GROUP BY c.evento_inscricao_id'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $resumo = [];

        foreach ($stmt->fetchAll() as $linha) {
            $resumo[(int) $linha['evento_inscricao_id']] = [
                'atividades' => (int) $linha['atividades'],
                'dias' => (int) $linha['dias'],
            ];
        }

        return $resumo;
    }

    /**
     * Presencas por tipo de atividade de todos os inscritos, na forma
     * [evento_inscricao_id => [tipo_id => quantas]].
     */
    public function porTipoDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.evento_inscricao_id, a.tipo_id, COUNT(*) AS total
               FROM evento_checkins c
               INNER JOIN evento_atividades a ON a.id = c.atividade_id
              WHERE a.evento_id = :evento AND a.tipo_id IS NOT NULL AND c.removido_em IS NULL
              GROUP BY c.evento_inscricao_id, a.tipo_id'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $porInscricao = [];

        foreach ($stmt->fetchAll() as $linha) {
            $inscricao = (int) $linha['evento_inscricao_id'];

            if (!isset($porInscricao[$inscricao])) {
                $porInscricao[$inscricao] = [];
            }

            $porInscricao[$inscricao][(int) $linha['tipo_id']] = (int) $linha['total'];
        }

        return $porInscricao;
    }
}
