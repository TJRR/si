<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: catalogo de naturezas de Trabalhos (relato de experiencia,
 * resultado de pesquisa, ...), cadastrado pelo Admin, por evento - mesmo
 * espirito de TrabalhoEixoTematicoRepository, nunca semeado via migration.
 */
class TrabalhoNaturezaRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_naturezas WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_naturezas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $natureza = $stmt->fetch();

        return $natureza !== false ? $natureza : null;
    }

    public function criar($eventoId, $nome, $descricao, $ordem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalho_naturezas (evento_id, nome, descricao, ordem) VALUES (:evento_id, :nome, :descricao, :ordem)'
        );
        $stmt->execute(['evento_id' => $eventoId, 'nome' => $nome, 'descricao' => $descricao, 'ordem' => $ordem]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trabalho_naturezas', $id, null, ['evento_id' => $eventoId, 'nome' => $nome]);

        return $id;
    }

    public function atualizar($id, $nome, $descricao, $ordem)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE trabalho_naturezas SET nome = :nome, descricao = :descricao, ordem = :ordem WHERE id = :id'
        );
        $stmt->execute(['nome' => $nome, 'descricao' => $descricao, 'ordem' => $ordem, 'id' => $id]);

        Auditoria::registrar('atualizar', 'trabalho_naturezas', $id, $antes, ['nome' => $nome, 'descricao' => $descricao, 'ordem' => $ordem]);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM trabalho_naturezas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'trabalho_naturezas', $id, $antes, null);
    }
}
