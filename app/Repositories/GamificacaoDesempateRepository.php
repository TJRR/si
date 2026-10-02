<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 58: cascata de desempate da classificacao geral (migration 186), no
 * molde de TrabalhoRegraDesempateRepository (Fase 49). O que cada criterio
 * significa mora em GamificacaoService::CRITERIOS_DESEMPATE; aqui ficam so'
 * a escolha e a ordem do Administrador.
 */
class GamificacaoDesempateRepository
{
    /**
     * Criterios na ordem da cascata. Leitura protegida: a classificacao do
     * participante sem cascata continua funcionando, com empate
     * compartilhado.
     */
    public function listarCriterios($eventoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT id, criterio, ordem FROM evento_gamificacao_desempate
                  WHERE evento_id = :evento ORDER BY ordem ASC, id ASC'
            );
            $stmt->execute(['evento' => (int) $eventoId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Gamificacao] Falha ao ler o desempate do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Acrescenta o criterio no fim da cascata. A chave unica
     * (evento_id, criterio) recusa o mesmo criterio duas vezes; devolve false
     * nesse caso.
     */
    public function adicionar($eventoId, $criterio)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM evento_gamificacao_desempate WHERE evento_id = :evento');
        $stmt->execute(['evento' => (int) $eventoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_gamificacao_desempate (evento_id, criterio, ordem) VALUES (:evento, :criterio, :ordem)'
            );
            $stmt->execute(['evento' => (int) $eventoId, 'criterio' => $criterio, 'ordem' => $proximaOrdem]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }

        Auditoria::registrar('criar', 'evento_gamificacao_desempate', (int) $pdo->lastInsertId(), null, [
            'evento_id' => (int) $eventoId,
            'criterio' => $criterio,
        ]);

        return true;
    }

    public function remover($eventoId, $id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_gamificacao_desempate WHERE id = :id AND evento_id = :evento');
        $stmt->execute(['id' => (int) $id, 'evento' => (int) $eventoId]);

        if ($stmt->rowCount() > 0) {
            Auditoria::registrar('remover', 'evento_gamificacao_desempate', (int) $id, ['evento_id' => (int) $eventoId], null);
        }
    }

    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_gamificacao_desempate SET ordem = :ordem WHERE id = :id AND evento_id = :evento');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento' => (int) $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_gamificacao_desempate', null, null, ['evento_id' => (int) $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
