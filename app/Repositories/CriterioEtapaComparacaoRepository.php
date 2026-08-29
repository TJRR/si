<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 37: vinculo criterio de avaliacao <-> etapas anteriores da mesma
 * trilha que o avaliador pode consultar para julgar aquele criterio.
 * Sem diff incremental - salvarVinculos() sempre substitui a selecao
 * inteira do criterio, mesmo padrao de CriterioCampoRepository.
 */
class CriterioEtapaComparacaoRepository
{
    public function listarEtapaIdsPorCriterio($criterioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT etapa_comparacao_id FROM criterio_etapa_comparacao WHERE criterio_id = :criterio_id');
        $stmt->execute(['criterio_id' => $criterioId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function salvarVinculos($criterioId, array $etapaIds)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $remover = $pdo->prepare('DELETE FROM criterio_etapa_comparacao WHERE criterio_id = :criterio_id');
            $remover->execute(['criterio_id' => $criterioId]);

            $inserir = $pdo->prepare('INSERT INTO criterio_etapa_comparacao (criterio_id, etapa_comparacao_id) VALUES (:criterio_id, :etapa_comparacao_id)');

            foreach ($etapaIds as $etapaId) {
                $inserir->execute(['criterio_id' => $criterioId, 'etapa_comparacao_id' => (int) $etapaId]);
            }

            $pdo->commit();
            Auditoria::registrar('salvar_vinculos', 'criterio_etapa_comparacao', $criterioId, null, ['etapa_ids' => $etapaIds]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
