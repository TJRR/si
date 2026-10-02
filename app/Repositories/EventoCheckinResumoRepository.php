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
 *
 * Fase 58 (decisao do dono): presenca na atividade que a propria pessoa
 * facilita (designacao ativa em evento_atividade_facilitadores) continua
 * registrada, para certificado e exportacao EJURR, mas nao conta para
 * bonus - no molde do representante, que nao pontua no proprio estande. A
 * exclusao usa a chave UNIQUE (atividade_id, usuario_id) daquela tabela.
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
                   INNER JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
                  WHERE c.evento_inscricao_id = :inscricao AND a.evento_id = :evento
                    AND c.removido_em IS NULL AND NOT EXISTS (
                        SELECT 1 FROM evento_atividade_facilitadores f
                         WHERE f.atividade_id = c.atividade_id AND f.usuario_id = ei.usuario_id AND f.removido_em IS NULL
                    )'
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
                   INNER JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
                  WHERE c.evento_inscricao_id = :inscricao AND a.evento_id = :evento
                    AND a.tipo_id IS NOT NULL AND c.removido_em IS NULL AND NOT EXISTS (
                        SELECT 1 FROM evento_atividade_facilitadores f
                         WHERE f.atividade_id = c.atividade_id AND f.usuario_id = ei.usuario_id AND f.removido_em IS NULL
                    )
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
               INNER JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
              WHERE a.evento_id = :evento AND c.removido_em IS NULL AND NOT EXISTS (
                    SELECT 1 FROM evento_atividade_facilitadores f
                     WHERE f.atividade_id = c.atividade_id AND f.usuario_id = ei.usuario_id AND f.removido_em IS NULL
                )
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
               INNER JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
              WHERE a.evento_id = :evento AND a.tipo_id IS NOT NULL AND c.removido_em IS NULL AND NOT EXISTS (
                    SELECT 1 FROM evento_atividade_facilitadores f
                     WHERE f.atividade_id = c.atividade_id AND f.usuario_id = ei.usuario_id AND f.removido_em IS NULL
                )
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

    /**
     * Fase 59: as atividades com presenca de UMA pessoa, com o periodo de
     * cada uma. Base do certificado: a contagem de atividades distintas, a de
     * dias distintos e a carga horaria (uniao dos intervalos, em
     * CertificadoElegibilidadeService) saem todas desta mesma lista, numa
     * consulta so'.
     *
     * DUAS DIFERENCAS DELIBERADAS em relacao as quatro consultas acima, que
     * servem aos bonus:
     *
     *   1. NAO exclui a atividade que a propria pessoa facilita. O comentario
     *      do topo desta classe registra a decisao da Fase 58: a presenca na
     *      atividade conduzida continua registrada "para certificado e
     *      exportacao EJURR", e so' nao conta para bonus. Quem conduziu e'
     *      quem mais esteve naquela sala.
     *   2. Devolve o periodo, e nao uma contagem, porque somar as duracoes
     *      exige saber onde cada atividade comeca e termina: as atividades da
     *      5a Semana se sobrepoem (duas das 16h as 18h de 04/11, outras duas
     *      das 14h as 18h de 06/11) e somar duracao a duracao declararia mais
     *      horas do que o evento tem.
     *
     * removido_em IS NULL continua valendo: presenca removida pelo
     * Administrador sai da conta do certificado como sai da dos bonus.
     */
    public function intervalosDaInscricao($eventoId, $inscricaoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT a.id AS atividade_id, a.nome, a.local, a.data_inicio, a.data_fim,
                        a.emite_certificado, a.certificado_fundo_url
                   FROM evento_checkins c
                   INNER JOIN evento_atividades a ON a.id = c.atividade_id
                  WHERE c.evento_inscricao_id = :inscricao AND a.evento_id = :evento
                    AND c.removido_em IS NULL
                  ORDER BY a.data_inicio ASC, a.id ASC'
            );
            $stmt->execute(['inscricao' => (int) $inscricaoId, 'evento' => (int) $eventoId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao listar as presencas da inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Fase 59: a mesma lista para TODOS os inscritos do evento de uma vez, na
     * forma [evento_inscricao_id => [linha, ...]]. Quem nao tem presenca nao
     * aparece, entao a apuracao do evento inteiro percorre so' quem pode ter
     * direito a alguma coisa.
     */
    public function intervalosDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.evento_inscricao_id, a.id AS atividade_id, a.nome, a.local,
                    a.data_inicio, a.data_fim, a.emite_certificado, a.certificado_fundo_url
               FROM evento_checkins c
               INNER JOIN evento_atividades a ON a.id = c.atividade_id
              WHERE a.evento_id = :evento AND c.removido_em IS NULL
              ORDER BY c.evento_inscricao_id ASC, a.data_inicio ASC, a.id ASC'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $porInscricao = [];

        foreach ($stmt->fetchAll() as $linha) {
            $inscricao = (int) $linha['evento_inscricao_id'];

            if (!isset($porInscricao[$inscricao])) {
                $porInscricao[$inscricao] = [];
            }

            $porInscricao[$inscricao][] = $linha;
        }

        return $porInscricao;
    }
}
