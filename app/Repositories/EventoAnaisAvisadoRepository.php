<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Trabalhos cujos autores ja receberam o aviso de publicacao dos Anais
 * (migration 208). Publicacao anterior a esta tabela nao tem linha.
 */
class EventoAnaisAvisadoRepository
{
    /**
     * Identificadores dos trabalhos ja avisados, como chaves do array.
     */
    public function idsAvisados($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT trabalho_id FROM evento_anais_trabalhos_avisados WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => (int) $eventoId]);

        $ids = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $ids[(int) $id] = true;
        }

        return $ids;
    }

    public function registrar($eventoId, array $trabalhoIds, $numeroVersao)
    {
        if ($trabalhoIds === []) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_anais_trabalhos_avisados (evento_id, trabalho_id, versao_numero)
             VALUES (:evento_id, :trabalho_id, :versao_numero)
             ON DUPLICATE KEY UPDATE versao_numero = VALUES(versao_numero), avisado_em = CURRENT_TIMESTAMP'
        );

        foreach (array_unique(array_map('intval', $trabalhoIds)) as $trabalhoId) {
            $stmt->execute([
                'evento_id' => (int) $eventoId,
                'trabalho_id' => $trabalhoId,
                'versao_numero' => (int) $numeroVersao,
            ]);
        }
    }
}
