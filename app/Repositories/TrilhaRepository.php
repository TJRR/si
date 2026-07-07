<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Core\Texto;

class TrilhaRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trilhas WHERE concurso_id = :concurso_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trilhas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $trilha = $stmt->fetch();

        return $trilha !== false ? $trilha : null;
    }

    public function criar($concursoId, $nome, $descricao, $ordem, $ativo)
    {
        $pdo = Database::conexao();
        $slug = $this->gerarSlugUnico($concursoId, $nome);

        $stmt = $pdo->prepare(
            'INSERT INTO trilhas (concurso_id, nome, slug, descricao, ordem, ativo)
             VALUES (:concurso_id, :nome, :slug, :descricao, :ordem, :ativo)'
        );
        $stmt->execute([
            'concurso_id' => $concursoId,
            'nome' => $nome,
            'slug' => $slug,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'ativo' => $ativo,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function atualizar($id, $nome, $descricao, $ordem, $ativo)
    {
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'UPDATE trilhas SET nome = :nome, descricao = :descricao, ordem = :ordem, ativo = :ativo WHERE id = :id'
        );
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'ativo' => $ativo,
            'id' => $id,
        ]);
    }

    private function gerarSlugUnico($concursoId, $nome)
    {
        $pdo = Database::conexao();
        $base = Texto::slugify($nome);
        $slug = $base;
        $sufixo = 2;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM trilhas WHERE concurso_id = :concurso_id AND slug = :slug');

        while (true) {
            $stmt->execute(['concurso_id' => $concursoId, 'slug' => $slug]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }

            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
    }
}
