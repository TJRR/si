<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class UsuarioParticipanteRepository
{
    public function vincular($usuarioId, $participanteId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO usuario_participante (usuario_id, participante_id) VALUES (:usuario_id, :participante_id)'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'participante_id' => $participanteId]);
    }

    public function participantesDoUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.*
             FROM participantes p
             INNER JOIN usuario_participante up ON up.participante_id = p.id
             WHERE up.usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Inverso de participantesDoUsuario() - usado para descobrir a quem
     * notificar quando um evento acontece do lado do participante (ex.:
     * rejeicao de inscricao), retorna os ids de usuario vinculados.
     */
    public function usuariosDoParticipante($participanteId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT usuario_id FROM usuario_participante WHERE participante_id = :participante_id'
        );
        $stmt->execute(['participante_id' => $participanteId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
