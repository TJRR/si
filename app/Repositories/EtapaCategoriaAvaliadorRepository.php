<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class EtapaCategoriaAvaliadorRepository
{
    public function listarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT eca.*, ca.nome AS categoria_nome
             FROM etapa_categoria_avaliadores eca
             INNER JOIN categorias_avaliador ca ON ca.id = eca.categoria_avaliador_id
             WHERE eca.etapa_id = :etapa_id
             ORDER BY ca.nome ASC'
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    /**
     * Substitui de uma vez todas as quantidades da etapa. $quantidadesPorCategoriaId
     * e um mapa [categoria_avaliador_id => quantidade]; categorias com
     * quantidade 0 (ou ausentes) nao geram linha.
     */
    public function salvarQuantidades($etapaId, array $quantidadesPorCategoriaId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $remover = $pdo->prepare('DELETE FROM etapa_categoria_avaliadores WHERE etapa_id = :etapa_id');
            $remover->execute(['etapa_id' => $etapaId]);

            $inserir = $pdo->prepare(
                'INSERT INTO etapa_categoria_avaliadores (etapa_id, categoria_avaliador_id, quantidade)
                 VALUES (:etapa_id, :categoria_id, :quantidade)'
            );

            foreach ($quantidadesPorCategoriaId as $categoriaId => $quantidade) {
                $quantidade = (int) $quantidade;

                if ($quantidade <= 0) {
                    continue;
                }

                $inserir->execute([
                    'etapa_id' => $etapaId,
                    'categoria_id' => $categoriaId,
                    'quantidade' => $quantidade,
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('salvar_quantidades', 'etapa_categoria_avaliadores', $etapaId, null, ['quantidades_por_categoria_id' => $quantidadesPorCategoriaId]);
    }
}
