<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 19 (#17): publicacao da pagina publica de equipes homologadas, por
 * trilha - mesmo padrao de "existencia de linha = publicado" ja usado em
 * ResultadoEtapaRepository/ResultadoTrilhaRepository, sem coluna booleana
 * solta.
 */
class HomologacaoPublicaRepository
{
    public function jaPublicado($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM homologacoes_publicadas WHERE trilha_id = :trilha_id');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function publicar($trilhaId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO homologacoes_publicadas (trilha_id, publicado_por, publicado_em)
             VALUES (:trilha_id, :publicado_por, NOW())
             ON DUPLICATE KEY UPDATE publicado_por = :publicado_por2, publicado_em = NOW()'
        );
        $stmt->execute([
            'trilha_id' => $trilhaId,
            'publicado_por' => $usuarioId,
            'publicado_por2' => $usuarioId,
        ]);

        Auditoria::registrar('publicar', 'homologacoes_publicadas', $trilhaId, null, ['publicado_por' => $usuarioId]);
    }

    public function reabrir($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM homologacoes_publicadas WHERE trilha_id = :trilha_id');
        $stmt->execute(['trilha_id' => $trilhaId]);

        Auditoria::registrar('reabrir', 'homologacoes_publicadas', $trilhaId, null, null);
    }
}
