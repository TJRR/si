<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 47: rate limiting de EventoAppController::validarPresenca(), inspirado
 * em LeituraCodigoFalhaRepository (Fase 43) mas com assinatura propria -
 * cada metodo recebe $atividadeId alem de $usuarioId, aceitando null. Quando
 * o codigo lido bate numa atividade real, o limite e' chaveado por
 * (usuario, atividade); quando nao bate em nenhuma (caso mais comum de erro
 * de leitura/digitacao), cai num "balde" comum (atividade_id IS NULL) - sem
 * isso, alguem errando em varias atividades diferentes ao longo do dia
 * poderia se bloquear sem ter errado 10x numa mesma atividade.
 */
class EventoAtividadeLeituraFalhaRepository
{
    public function registrarFalha($usuarioId, $atividadeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_atividade_leituras_falhas (usuario_id, atividade_id) VALUES (:usuario_id, :atividade_id)'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'atividade_id' => $atividadeId]);
    }

    public function contarFalhasRecentes($usuarioId, $atividadeId, $minutos = 30)
    {
        $pdo = Database::conexao();
        $condicaoAtividade = $atividadeId !== null ? 'atividade_id = :atividade_id' : 'atividade_id IS NULL';
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM evento_atividade_leituras_falhas
             WHERE usuario_id = :usuario_id AND {$condicaoAtividade} AND criado_em >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)"
        );
        $stmt->bindValue('usuario_id', $usuarioId);
        if ($atividadeId !== null) {
            $stmt->bindValue('atividade_id', $atividadeId);
        }
        $stmt->bindValue('minutos', $minutos, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function limparFalhas($usuarioId, $atividadeId)
    {
        $pdo = Database::conexao();
        $condicaoAtividade = $atividadeId !== null ? 'atividade_id = :atividade_id' : 'atividade_id IS NULL';
        $stmt = $pdo->prepare("DELETE FROM evento_atividade_leituras_falhas WHERE usuario_id = :usuario_id AND {$condicaoAtividade}");
        $stmt->bindValue('usuario_id', $usuarioId);
        if ($atividadeId !== null) {
            $stmt->bindValue('atividade_id', $atividadeId);
        }
        $stmt->execute();
    }
}
