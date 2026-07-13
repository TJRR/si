<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class AvaliadorCategoriaRepository
{
    /**
     * Upsert: cada avaliador tem no maximo 1 categoria por concurso
     * (uq_avaliador_categorias_usuario_concurso).
     */
    public function atribuir($usuarioId, $concursoId, $categoriaAvaliadorId)
    {
        $antes = $this->categoriaDoUsuario($usuarioId, $concursoId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO avaliador_categorias (usuario_id, concurso_id, categoria_avaliador_id)
             VALUES (:usuario_id, :concurso_id, :categoria_id)
             ON DUPLICATE KEY UPDATE categoria_avaliador_id = VALUES(categoria_avaliador_id)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'concurso_id' => $concursoId,
            'categoria_id' => $categoriaAvaliadorId,
        ]);

        Auditoria::registrar('atribuir', 'avaliador_categorias', $usuarioId, $antes, [
            'usuario_id' => $usuarioId,
            'concurso_id' => $concursoId,
            'categoria_avaliador_id' => $categoriaAvaliadorId,
        ]);
    }

    public function categoriaDoUsuario($usuarioId, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ac.*, ca.nome AS categoria_nome
             FROM avaliador_categorias ac
             INNER JOIN categorias_avaliador ca ON ca.id = ac.categoria_avaliador_id
             WHERE ac.usuario_id = :usuario_id AND ac.concurso_id = :concurso_id
             LIMIT 1'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'concurso_id' => $concursoId]);

        $vinculo = $stmt->fetch();

        return $vinculo !== false ? $vinculo : null;
    }

    public function listarUsuariosPorCategoria($categoriaAvaliadorId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT u.*
             FROM usuarios u
             INNER JOIN avaliador_categorias ac ON ac.usuario_id = u.id
             WHERE ac.categoria_avaliador_id = :categoria_id
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['categoria_id' => $categoriaAvaliadorId]);

        return $stmt->fetchAll();
    }
}
