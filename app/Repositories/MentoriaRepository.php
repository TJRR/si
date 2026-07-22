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
            'SELECT mh.*, u.nome AS mentor_nome, e.nome_equipe
             FROM mentoria_horarios mh
             JOIN usuarios u ON u.id = mh.mentor_usuario_id
             LEFT JOIN equipes e ON e.id = mh.equipe_id
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

    public function criar($concursoId, $mentorUsuarioId, $dataInicio, $dataFim, $observacao)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO mentoria_horarios (concurso_id, mentor_usuario_id, data_inicio, data_fim, observacao)
             VALUES (:concurso_id, :mentor_usuario_id, :data_inicio, :data_fim, :observacao)'
        );
        $dados = [
            'concurso_id' => $concursoId,
            'mentor_usuario_id' => $mentorUsuarioId,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'observacao' => $observacao,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'mentoria_horarios', $id, null, $dados);

        return $id;
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
