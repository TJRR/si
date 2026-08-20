<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 19 (#106): agendamento de mentorias. Mentor = usuario com perfil
 * administrador/suporte (mentor_usuario_id aponta direto pra `usuarios`,
 * sem cadastro/perfil proprio). equipe_id NULL = vago; preenchido =
 * reservado. Modelo "admin cria horario vago, equipe reserva".
 */
class MentoriaRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT mh.*, u.nome AS mentor_nome, e.nome_equipe,
                    et.nome AS etapa_nome, t.nome AS etapa_trilha_nome
             FROM mentoria_horarios mh
             JOIN usuarios u ON u.id = mh.mentor_usuario_id
             LEFT JOIN equipes e ON e.id = mh.equipe_id
             LEFT JOIN etapas et ON et.id = mh.etapa_id
             LEFT JOIN trilhas t ON t.id = et.trilha_id
             WHERE mh.concurso_id = :concurso_id
             ORDER BY mh.data_inicio ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function listarVagosPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT mh.*, u.nome AS mentor_nome
             FROM mentoria_horarios mh
             JOIN usuarios u ON u.id = mh.mentor_usuario_id
             WHERE mh.concurso_id = :concurso_id AND mh.equipe_id IS NULL AND mh.data_inicio > NOW()
             ORDER BY mh.data_inicio ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 24: mentoria e' opcional - o botão "Agendar Mentoria" só aparece
     * no painel do participante se algum mentor já tiver criado ao menos
     * um horário para o concurso (mesmo que todos já reservados).
     */
    public function existeParaConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mentoria_horarios WHERE concurso_id = :concurso_id');
        $stmt->execute(['concurso_id' => $concursoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function listarReservasDaEquipe($equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT mh.*, u.nome AS mentor_nome
             FROM mentoria_horarios mh
             JOIN usuarios u ON u.id = mh.mentor_usuario_id
             WHERE mh.equipe_id = :equipe_id
             ORDER BY mh.data_inicio ASC'
        );
        $stmt->execute(['equipe_id' => $equipeId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM mentoria_horarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $horario = $stmt->fetch();

        return $horario !== false ? $horario : null;
    }

    public function criar($concursoId, $mentorUsuarioId, $dataInicio, $dataFim, $linkMeet, $observacao, $integracaoGoogle = false, $etapaId = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO mentoria_horarios (concurso_id, etapa_id, mentor_usuario_id, data_inicio, data_fim, link_meet, observacao, integracao_google)
             VALUES (:concurso_id, :etapa_id, :mentor_usuario_id, :data_inicio, :data_fim, :link_meet, :observacao, :integracao_google)'
        );
        $dados = [
            'concurso_id' => $concursoId,
            'etapa_id' => $etapaId,
            'mentor_usuario_id' => $mentorUsuarioId,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'link_meet' => $linkMeet,
            'observacao' => $observacao,
            'integracao_google' => $integracaoGoogle ? 1 : 0,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'mentoria_horarios', $id, null, $dados);

        return $id;
    }

    /**
     * Fase 31: colunas da integracao com o Google Agenda - usado tanto logo
     * apos criar()/verificarNovamente() (preenche google_event_id etc.)
     * quanto na reconciliacao sob demanda (so' atualiza meet_link/
     * meet_pendente/google_sincronizado_em). $colunas usa as chaves de
     * GoogleCalendarSyncService (meet_link, nao link_meet) - o mapeamento
     * pro nome real da coluna e' feito aqui dentro.
     */
    /**
     * Fase 34: edicao de um horario ainda nao iniciado. Nao toca em
     * equipe_id/reservado_em (a reserva e' acao do participante) nem nas
     * colunas do Google (essas passam por atualizarGoogle(), que audita com
     * acao propria).
     */
    public function atualizar($id, $dataInicio, $dataFim, $linkMeet, $observacao, $etapaId)
    {
        $antes = $this->buscarPorId($id);
        $dados = [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'link_meet' => $linkMeet,
            'observacao' => $observacao,
            'etapa_id' => $etapaId,
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE mentoria_horarios
                SET data_inicio = :data_inicio, data_fim = :data_fim, link_meet = :link_meet,
                    observacao = :observacao, etapa_id = :etapa_id
              WHERE id = :id'
        );
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'mentoria_horarios', $id, $antes, $dados);
    }

    /**
     * Fase 34: etapas distintas vinculadas aos horarios do concurso, com
     * NULL preservado (= horario aberto a todos). O painel do participante
     * usa isso pra decidir se acende o botao sem precisar carregar e
     * filtrar a listagem inteira.
     */
    public function etapasVinculadasNoConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT DISTINCT etapa_id FROM mentoria_horarios WHERE concurso_id = :concurso_id');
        $stmt->execute(['concurso_id' => $concursoId]);

        return array_map(function ($valor) {
            return $valor === null ? null : (int) $valor;
        }, $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function atualizarGoogle($id, array $colunas)
    {
        $mapa = [
            'google_event_id' => 'google_event_id',
            'google_calendar_id' => 'google_calendar_id',
            'meet_link' => 'link_meet',
            'meet_link_origem' => 'meet_link_origem',
            'meet_pendente' => 'meet_pendente',
            'google_conference_id' => 'google_conference_id',
            'google_sincronizado_em' => 'google_sincronizado_em',
        ];

        $campos = [];

        foreach ($mapa as $chave => $coluna) {
            if (array_key_exists($chave, $colunas)) {
                $campos[$coluna] = $colunas[$chave];
            }
        }

        if (empty($campos)) {
            return;
        }

        $antes = $this->buscarPorId($id);
        $sets = [];

        foreach (array_keys($campos) as $coluna) {
            $sets[] = "$coluna = :$coluna";
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE mentoria_horarios SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($campos + ['id' => $id]);

        Auditoria::registrar('atualizar_google', 'mentoria_horarios', $id, $antes, $campos);
    }

    /**
     * Fase 32: horarios prontos pra ter a presenca capturada pelo cron
     * (database/capturar_presenca_google_meet.php).
     *
     * O intervalo de 2h depois de data_fim e' margem: a reuniao pode se
     * estender alem do horario marcado, e o Google leva um tempo pra fechar o
     * conferenceRecord depois que a chamada termina de verdade.
     *
     * O filtro por presenca_ultima_tentativa_em e' o que garante a cadencia de
     * 30 min entre tentativas INDEPENDENTE do intervalo configurado no
     * crontab - sem ele, um cron de 15 min queimaria as 30 tentativas na
     * metade do tempo previsto.
     *
     * LIMIT + ORDER BY data_fim: protege contra rajada (backfill retroativo,
     * ou cron que ficou fora do ar e voltou com backlog acumulado), drenando
     * a fila aos poucos e priorizando os mais antigos, que estao mais perto de
     * vencer os 30 dias de retencao do Google.
     */
    public function listarPendentesDePresenca($limite)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT mh.*, u.email AS organizador_email
             FROM mentoria_horarios mh
             JOIN usuarios u ON u.id = mh.mentor_usuario_id
             WHERE mh.integracao_google = 1
               AND mh.google_conference_id IS NOT NULL
               AND mh.presenca_status = \'pendente\'
               AND mh.data_fim <= (NOW() - INTERVAL 2 HOUR)
               AND (mh.presenca_ultima_tentativa_em IS NULL
                    OR mh.presenca_ultima_tentativa_em <= (NOW() - INTERVAL 30 MINUTE))
             ORDER BY mh.data_fim ASC
             LIMIT :limite'
        );
        $stmt->bindValue('limite', (int) $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fase 32: estado da captura de presenca. Sem Auditoria::registrar -
     * e' sincronizacao automatica de estado externo (mesma regra de
     * GooglePresencaRepository), nao acao de usuario. A excecao e' o reset
     * manual pelo botao "Reprocessar presenca", auditado no controller.
     */
    public function atualizarPresenca($id, array $colunas)
    {
        $permitidas = ['presenca_status', 'presenca_tentativas', 'presenca_ultima_tentativa_em', 'presenca_capturada_em'];
        $campos = array_intersect_key($colunas, array_flip($permitidas));

        if (empty($campos)) {
            return;
        }

        $sets = [];

        foreach (array_keys($campos) as $coluna) {
            $sets[] = "$coluna = :$coluna";
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE mentoria_horarios SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($campos + ['id' => $id]);
    }

    /**
     * Checagem otimista pelo proprio WHERE (equipe_id IS NULL): se
     * rowCount() vier 0, o horario ja foi reservado por outra equipe
     * entre a equipe ver a lista e clicar "Reservar" - quem chama deve
     * tratar como erro, nunca sobrescrever.
     */
    public function reservar($id, $equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE mentoria_horarios SET equipe_id = :equipe_id, reservado_em = NOW()
             WHERE id = :id AND equipe_id IS NULL'
        );
        $stmt->execute(['equipe_id' => $equipeId, 'id' => $id]);

        $sucesso = $stmt->rowCount() > 0;

        if ($sucesso) {
            Auditoria::registrar('reservar', 'mentoria_horarios', $id, null, ['equipe_id' => $equipeId]);
        }

        return $sucesso;
    }

    public function cancelarReserva($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE mentoria_horarios SET equipe_id = NULL, reservado_em = NULL WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('cancelar_reserva', 'mentoria_horarios', $id, $antes, null);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM mentoria_horarios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'mentoria_horarios', $id, $antes, null);
    }
}
