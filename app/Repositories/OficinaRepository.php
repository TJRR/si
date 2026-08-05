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

    public function criar($concursoId, $tema, $dataInicio, $dataFim, $linkMeet, $observacao, $criadoPor)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO oficina_horarios (concurso_id, tema, data_inicio, data_fim, link_meet, observacao, criado_por)
             VALUES (:concurso_id, :tema, :data_inicio, :data_fim, :link_meet, :observacao, :criado_por)'
        );
        $dados = [
            'concurso_id' => $concursoId,
            'tema' => $tema,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'link_meet' => $linkMeet,
            'observacao' => $observacao,
            'criado_por' => $criadoPor,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'oficina_horarios', $id, null, $dados);

        return $id;
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
