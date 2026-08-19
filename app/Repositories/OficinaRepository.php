<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 24: agendamento de oficinas - encontros coletivos com tema
 * pre-definido, sem "mentor" (o organizador do concurso conduz).
 * Diferente de mentoria (1 equipe por horario, reserva exclusiva), uma
 * oficina aceita varias equipes no mesmo horario - por isso a inscricao
 * fica em oficina_inscricoes (N:N) em vez de uma coluna equipe_id.
 */
class OficinaRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT oh.*, u.nome AS criado_por_nome,
                    (SELECT COUNT(*) FROM oficina_inscricoes oi WHERE oi.oficina_horario_id = oh.id) AS total_inscritos
             FROM oficina_horarios oh
             JOIN usuarios u ON u.id = oh.criado_por
             WHERE oh.concurso_id = :concurso_id
             ORDER BY oh.data_inicio ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function listarFuturasPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT oh.*,
                    (SELECT COUNT(*) FROM oficina_inscricoes oi WHERE oi.oficina_horario_id = oh.id) AS total_inscritos
             FROM oficina_horarios oh
             WHERE oh.concurso_id = :concurso_id AND oh.data_inicio > NOW()
             ORDER BY oh.data_inicio ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM oficina_horarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $horario = $stmt->fetch();

        return $horario !== false ? $horario : null;
    }

    public function criar($concursoId, $tema, $dataInicio, $dataFim, $linkMeet, $observacao, $criadoPor, $integracaoGoogle = false)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO oficina_horarios (concurso_id, tema, data_inicio, data_fim, link_meet, observacao, criado_por, integracao_google)
             VALUES (:concurso_id, :tema, :data_inicio, :data_fim, :link_meet, :observacao, :criado_por, :integracao_google)'
        );
        $dados = [
            'concurso_id' => $concursoId,
            'tema' => $tema,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'link_meet' => $linkMeet,
            'observacao' => $observacao,
            'criado_por' => $criadoPor,
            'integracao_google' => $integracaoGoogle ? 1 : 0,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'oficina_horarios', $id, null, $dados);

        return $id;
    }

    /**
     * Fase 31: mesma logica de MentoriaRepository::atualizarGoogle() - ver
     * comentario la' (mapeamento meet_link -> coluna link_meet, etc).
     */
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
        $stmt = $pdo->prepare('UPDATE oficina_horarios SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($campos + ['id' => $id]);

        Auditoria::registrar('atualizar_google', 'oficina_horarios', $id, $antes, $campos);
    }

    /**
     * Fase 32: horarios prontos pra ter a presenca capturada pelo cron -
     * mesma logica de MentoriaRepository::listarPendentesDePresenca(), ver
     * o comentario completo la' (margem de 2h, cadencia de 30 min
     * independente do crontab, LIMIT contra rajada).
     */
    public function listarPendentesDePresenca($limite)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT oh.*, u.email AS organizador_email
             FROM oficina_horarios oh
             JOIN usuarios u ON u.id = oh.criado_por
             WHERE oh.integracao_google = 1
               AND oh.google_conference_id IS NOT NULL
               AND oh.presenca_status = \'pendente\'
               AND oh.data_fim <= (NOW() - INTERVAL 2 HOUR)
               AND (oh.presenca_ultima_tentativa_em IS NULL
                    OR oh.presenca_ultima_tentativa_em <= (NOW() - INTERVAL 30 MINUTE))
             ORDER BY oh.data_fim ASC
             LIMIT :limite'
        );
        $stmt->bindValue('limite', (int) $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fase 32: estado da captura de presenca - ver
     * MentoriaRepository::atualizarPresenca() para a justificativa de nao
     * registrar em Auditoria.
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
        $stmt = $pdo->prepare('UPDATE oficina_horarios SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($campos + ['id' => $id]);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();

        $stmt = $pdo->prepare('DELETE FROM oficina_inscricoes WHERE oficina_horario_id = :id');
        $stmt->execute(['id' => $id]);

        $stmt = $pdo->prepare('DELETE FROM oficina_horarios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'oficina_horarios', $id, $antes, null);
    }

    /**
     * Fase 24: oficina e' opcional - o botão "Oficinas" só aparece no
     * painel do participante se algum horário já tiver sido criado pro
     * concurso.
     */
    public function existeParaConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM oficina_horarios WHERE concurso_id = :concurso_id');
        $stmt->execute(['concurso_id' => $concursoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function listarInscritos($horarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT oi.*, e.nome_equipe
             FROM oficina_inscricoes oi
             JOIN equipes e ON e.id = oi.equipe_id
             WHERE oi.oficina_horario_id = :horario_id
             ORDER BY e.nome_equipe ASC'
        );
        $stmt->execute(['horario_id' => $horarioId]);

        return $stmt->fetchAll();
    }

    public function listarInscricoesDaEquipe($equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT oh.*
             FROM oficina_inscricoes oi
             JOIN oficina_horarios oh ON oh.id = oi.oficina_horario_id
             WHERE oi.equipe_id = :equipe_id
             ORDER BY oh.data_inicio ASC'
        );
        $stmt->execute(['equipe_id' => $equipeId]);

        return $stmt->fetchAll();
    }

    public function estaInscrita($horarioId, $equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM oficina_inscricoes WHERE oficina_horario_id = :horario_id AND equipe_id = :equipe_id'
        );
        $stmt->execute(['horario_id' => $horarioId, 'equipe_id' => $equipeId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function inscrever($horarioId, $equipeId)
    {
        if ($this->estaInscrita($horarioId, $equipeId)) {
            return false;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('INSERT INTO oficina_inscricoes (oficina_horario_id, equipe_id) VALUES (:horario_id, :equipe_id)');
        $stmt->execute(['horario_id' => $horarioId, 'equipe_id' => $equipeId]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('inscrever', 'oficina_inscricoes', $id, null, ['oficina_horario_id' => $horarioId, 'equipe_id' => $equipeId]);

        return true;
    }

    public function cancelarInscricao($horarioId, $equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM oficina_inscricoes WHERE oficina_horario_id = :horario_id AND equipe_id = :equipe_id');
        $stmt->execute(['horario_id' => $horarioId, 'equipe_id' => $equipeId]);

        Auditoria::registrar('cancelar_inscricao', 'oficina_inscricoes', $horarioId, null, ['equipe_id' => $equipeId]);
    }
}
