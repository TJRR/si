<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Limite de tentativas de EventoAppController::validarCodigo(), em tabela
 * dedicada: nunca a mesma tabela da entrada nem a de outra leitura, para
 * nao misturar contagens. Ver Implantar.md, secao 13.6.
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
