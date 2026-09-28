<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 54: limite de tentativas da leitura do codigo do estande, por
 * usuario, em tabela propria (mesmo contrato de LeituraCodigoFalhaRepository,
 * Fase 43). Nao ha coluna de estande porque nao existe falha depois de achar
 * um estande real: so' conta codigo inexistente.
 */
class EstandeLeituraFalhaRepository
{
    public function registrarFalha($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('INSERT INTO evento_estande_leituras_falhas (usuario_id) VALUES (:usuario_id)');
        $stmt->execute(['usuario_id' => $usuarioId]);
    }

    public function contarFalhasRecentes($usuarioId, $minutos = 30)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_estande_leituras_falhas
             WHERE usuario_id = :usuario_id AND criado_em >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)'
        );
        $stmt->bindValue('usuario_id', $usuarioId);
        $stmt->bindValue('minutos', $minutos, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function limparFalhas($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_estande_leituras_falhas WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);
    }
}
