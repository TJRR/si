<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class ResultadoTrilhaRepository
{
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT rt.*, e.nome_equipe
             FROM resultados_trilha rt
             INNER JOIN equipes e ON e.id = rt.equipe_id
             WHERE rt.trilha_id = :trilha_id
             ORDER BY rt.colocacao ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function jaPublicado($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM resultados_trilha WHERE trilha_id = :trilha_id');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function publicar($trilhaId, array $linhas, $usuarioId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $remover = $pdo->prepare('DELETE FROM resultados_trilha WHERE trilha_id = :trilha_id');
            $remover->execute(['trilha_id' => $trilhaId]);

            $inserir = $pdo->prepare(
                'INSERT INTO resultados_trilha (equipe_id, trilha_id, nf, colocacao, publicado_por)
                 VALUES (:equipe_id, :trilha_id, :nf, :colocacao, :publicado_por)'
            );

            foreach ($linhas as $linha) {
                $inserir->execute([
                    'equipe_id' => $linha['equipe_id'],
                    'trilha_id' => $trilhaId,
                    'nf' => $linha['nf'],
                    'colocacao' => $linha['colocacao'],
                    'publicado_por' => $usuarioId,
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function reabrir($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM resultados_trilha WHERE trilha_id = :trilha_id');
        $stmt->execute(['trilha_id' => $trilhaId]);
    }
}
