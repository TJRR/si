<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (4.5) - biblioteca de midia GLOBAL (reaproveitavel entre edicoes),
 * concurso_id so' guarda a origem/filtro opcional, nao restringe uso.
 */
class MidiaRepository
{
    public function listar($tipo = null)
    {
        $pdo = Database::conexao();

        if ($tipo !== null) {
            $stmt = $pdo->prepare('SELECT * FROM midias WHERE tipo = :tipo ORDER BY criado_em DESC');
            $stmt->execute(['tipo' => $tipo]);
        } else {
            $stmt = $pdo->query('SELECT * FROM midias ORDER BY criado_em DESC');
        }

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM midias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $midia = $stmt->fetch();

        return $midia !== false ? $midia : null;
    }

    public function criar(array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO midias (concurso_id, arquivo_path, tipo, alt_text, titulo, descricao, criado_por)
             VALUES (:concurso_id, :arquivo_path, :tipo, :alt_text, :titulo, :descricao, :criado_por)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'midias', $id, null, $dados);

        return $id;
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM midias WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'midias', $id, $antes, null);
    }
}
