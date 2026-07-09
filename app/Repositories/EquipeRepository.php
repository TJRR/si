<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class EquipeRepository
{
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM equipes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $equipe = $stmt->fetch();

        return $equipe !== false ? $equipe : null;
    }

    public function buscarPorTrilhaENome($trilhaId, $nomeEquipe)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM equipes WHERE trilha_id = :trilha_id AND nome_equipe = :nome_equipe LIMIT 1');
        $stmt->execute(['trilha_id' => $trilhaId, 'nome_equipe' => $nomeEquipe]);

        $equipe = $stmt->fetch();

        return $equipe !== false ? $equipe : null;
    }

    public function criar($trilhaId, $nomeEquipe, $vinculoInstitucional, $observacoes)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO equipes (trilha_id, nome_equipe, vinculo_institucional, observacoes, importado_em)
             VALUES (:trilha_id, :nome_equipe, :vinculo_institucional, :observacoes, NOW())'
        );
        $stmt->execute([
            'trilha_id' => $trilhaId,
            'nome_equipe' => $nomeEquipe,
            'vinculo_institucional' => $vinculoInstitucional !== '' ? $vinculoInstitucional : null,
            'observacoes' => $observacoes !== '' ? $observacoes : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function vincularParticipante($equipeId, $participanteId, $papel)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO equipe_participante (equipe_id, participante_id, papel) VALUES (:equipe_id, :participante_id, :papel)'
        );
        $stmt->execute([
            'equipe_id' => $equipeId,
            'participante_id' => $participanteId,
            'papel' => $papel,
        ]);
    }

    public function desvincularParticipante($equipeId, $participanteId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'DELETE FROM equipe_participante WHERE equipe_id = :equipe_id AND participante_id = :participante_id'
        );
        $stmt->execute(['equipe_id' => $equipeId, 'participante_id' => $participanteId]);
    }

    public function listarComContagemParticipantes($trilhaId = null)
    {
        $pdo = Database::conexao();

        $sql = 'SELECT e.*, t.nome AS trilha_nome, COUNT(ep.participante_id) AS total_participantes
                FROM equipes e
                JOIN trilhas t ON t.id = e.trilha_id
                LEFT JOIN equipe_participante ep ON ep.equipe_id = e.id';

        $parametros = [];

        if ($trilhaId !== null) {
            $sql .= ' WHERE e.trilha_id = :trilha_id';
            $parametros['trilha_id'] = $trilhaId;
        }

        $sql .= ' GROUP BY e.id ORDER BY t.nome ASC, e.nome_equipe ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function listarParticipantes($equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.*, ep.papel
             FROM participantes p
             JOIN equipe_participante ep ON ep.participante_id = p.id
             WHERE ep.equipe_id = :equipe_id
             ORDER BY ep.papel ASC, p.nome ASC'
        );
        $stmt->execute(['equipe_id' => $equipeId]);

        return $stmt->fetchAll();
    }
}
