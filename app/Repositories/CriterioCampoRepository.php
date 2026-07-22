<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 19 (#10): vinculo campo do formulario <-> criterio de avaliacao,
 * usado pra filtrar o conteudo mostrado em cada aba da tela do avaliador.
 * Sem diff incremental - salvar() sempre substitui a selecao inteira do
 * criterio, mesmo padrao ja usado pras redes sociais do Contato.
 */
class CriterioCampoRepository
{
    public function listarCampoIdsPorCriterio($criterioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT campo_id FROM criterio_campo WHERE criterio_id = :criterio_id');
        $stmt->execute(['criterio_id' => $criterioId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function salvarVinculos($criterioId, array $campoIds)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $remover = $pdo->prepare('DELETE FROM criterio_campo WHERE criterio_id = :criterio_id');
            $remover->execute(['criterio_id' => $criterioId]);

            $inserir = $pdo->prepare('INSERT INTO criterio_campo (criterio_id, campo_id) VALUES (:criterio_id, :campo_id)');

            foreach ($campoIds as $campoId) {
                $inserir->execute(['criterio_id' => $criterioId, 'campo_id' => (int) $campoId]);
            }

            $pdo->commit();
            Auditoria::registrar('salvar_vinculos', 'criterio_campo', $criterioId, null, ['campo_ids' => $campoIds]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
