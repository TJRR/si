<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Tentativas falhas da tela "Conferir cracha" (migration 210), em tabela
 * propria, separada da contagem de "Conectar com participante". Ver Implantar.md, secao 13.6.
 */
class CrachaConferenciaFalhaRepository
{
    const LIMITE_TENTATIVAS = 10;
    const JANELA_MINUTOS = 30;

    public function registrarFalha($usuarioId, $eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('INSERT INTO evento_cracha_conferencia_falhas (usuario_id, evento_id) VALUES (:usuario_id, :evento_id)');
        $stmt->execute(['usuario_id' => (int) $usuarioId, 'evento_id' => (int) $eventoId]);
    }

    public function excedeuOLimite($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_cracha_conferencia_falhas
             WHERE usuario_id = :usuario_id AND criado_em >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)'
        );
        $stmt->bindValue('usuario_id', (int) $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue('minutos', self::JANELA_MINUTOS, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() >= self::LIMITE_TENTATIVAS;
    }

    public function limparFalhas($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_cracha_conferencia_falhas WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => (int) $usuarioId]);
    }
}
