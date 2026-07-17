<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.9) - eventos avulsos do cronograma publico, cadastrados
 * manualmente pelo admin (cerimonia, live) que nao sao uma Etapa formal.
 */
class EventoCronogramaRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM eventos_cronograma WHERE concurso_id = :concurso_id ORDER BY data_inicio ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM eventos_cronograma WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $evento = $stmt->fetch();

        return $evento !== false ? $evento : null;
    }

    public function criar($concursoId, array $dados)
    {
        $campos = $dados + ['concurso_id' => $concursoId];
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO eventos_cronograma (concurso_id, etapa_id, titulo, descricao, data_inicio, data_fim, ordem)
             VALUES (:concurso_id, :etapa_id, :titulo, :descricao, :data_inicio, :data_fim, :ordem)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'eventos_cronograma', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE eventos_cronograma SET etapa_id = :etapa_id, titulo = :titulo, descricao = :descricao,
             data_inicio = :data_inicio, data_fim = :data_fim WHERE id = :id'
        );
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'eventos_cronograma', $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM eventos_cronograma WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'eventos_cronograma', $id, $antes, null);
    }
}
