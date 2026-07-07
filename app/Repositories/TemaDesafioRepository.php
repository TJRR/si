<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class TemaDesafioRepository
{
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM temas_desafios WHERE trilha_id = :trilha_id ORDER BY nome ASC');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function listarAtivosPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM temas_desafios WHERE trilha_id = :trilha_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM temas_desafios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $tema = $stmt->fetch();

        return $tema !== false ? $tema : null;
    }

    public function criar($trilhaId, $nome, $descricaoLonga, $ativo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO temas_desafios (trilha_id, nome, descricao_longa, ativo)
             VALUES (:trilha_id, :nome, :descricao_longa, :ativo)'
        );
        $stmt->execute([
            'trilha_id' => $trilhaId,
            'nome' => $nome,
            'descricao_longa' => $descricaoLonga !== '' ? $descricaoLonga : null,
            'ativo' => $ativo,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function atualizar($id, $nome, $descricaoLonga, $ativo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE temas_desafios SET nome = :nome, descricao_longa = :descricao_longa, ativo = :ativo WHERE id = :id'
        );
        $stmt->execute([
            'nome' => $nome,
            'descricao_longa' => $descricaoLonga !== '' ? $descricaoLonga : null,
            'ativo' => $ativo,
            'id' => $id,
        ]);
    }
}
