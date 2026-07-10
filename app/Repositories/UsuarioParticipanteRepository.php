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
}
