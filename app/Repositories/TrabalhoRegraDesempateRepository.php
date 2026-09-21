<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: regras de desempate de Trabalhos, em cascata (ordem ASC),
 * cadastradas pelo Admin, por evento - mesmo estilo de regras_desempate
 * do Concurso, tabela propria sem FK para etapas.
 */
class TrabalhoRegraDesempateRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT r.*, c.nome AS criterio_nome
             FROM trabalho_regras_desempate r
             LEFT JOIN trabalho_criterios c ON c.id = r.criterio_id
             WHERE r.evento_id = :evento_id
             ORDER BY r.ordem ASC, r.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_regras_desempate WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $regra = $stmt->fetch();

        return $regra !== false ? $regra : null;
    }

    public function criar($eventoId, $tipo, $criterioId, $ordem, $direcao)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalho_regras_desempate (evento_id, tipo, criterio_id, ordem, direcao)
             VALUES (:evento_id, :tipo, :criterio_id, :ordem, :direcao)'
        );
        $stmt->execute([
            'evento_id' => $eventoId, 'tipo' => $tipo, 'criterio_id' => $criterioId,
            'ordem' => $ordem, 'direcao' => $direcao,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trabalho_regras_desempate', $id, null, ['evento_id' => $eventoId, 'tipo' => $tipo]);

        return $id;
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM trabalho_regras_desempate WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'trabalho_regras_desempate', $id, $antes, null);
    }
}
