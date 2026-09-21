<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 43: rate limiting de EventoAppController::validarCodigo() por
 * usuario_id (o leitor ja' esta' autenticado, sem necessidade de rastrear
 * IP). Mesmo contrato de TentativaLoginRepository (Fase 31, achado #11), mas
 * em tabela dedicada - nunca a mesma tabela do login, para nao misturar
 * "errou a leitura de um codigo" com "esta' quase bloqueado para logar".
 */
class LeituraCodigoFalhaRepository
{
    public function registrarFalha($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_leituras_codigo_falhas (usuario_id) VALUES (:usuario_id)'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
    }

    public function contarFalhasRecentes($usuarioId, $minutos = 30)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM evento_leituras_codigo_falhas
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
        $stmt = $pdo->prepare('DELETE FROM evento_leituras_codigo_falhas WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);
    }
}
