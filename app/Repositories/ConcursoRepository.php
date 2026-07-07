<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;
use App\Core\Texto;

class ConcursoRepository
{
    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM concursos ORDER BY criado_em DESC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM concursos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $concurso = $stmt->fetch();

        return $concurso !== false ? $concurso : null;
    }

    public function criar($nome, $descricao, $dataInicio, $dataFim, $status)
    {
        $pdo = Database::conexao();
        $slug = $this->gerarSlugUnico($nome);

        $stmt = $pdo->prepare(
            'INSERT INTO concursos (nome, slug, descricao, data_inicio, data_fim, status)
             VALUES (:nome, :slug, :descricao, :data_inicio, :data_fim, :status)'
        );
        $stmt->execute([
            'nome' => $nome,
            'slug' => $slug,
            'descricao' => $descricao !== '' ? $descricao : null,
            'data_inicio' => $dataInicio !== '' ? $dataInicio : null,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'status' => $status,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function atualizar($id, $nome, $descricao, $dataInicio, $dataFim, $status)
    {
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'UPDATE concursos
             SET nome = :nome, descricao = :descricao, data_inicio = :data_inicio,
                 data_fim = :data_fim, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'data_inicio' => $dataInicio !== '' ? $dataInicio : null,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'status' => $status,
            'id' => $id,
        ]);
    }

    private function gerarSlugUnico($nome)
    {
        $pdo = Database::conexao();
        $base = Texto::slugify($nome);
        $slug = $base;
        $sufixo = 2;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM concursos WHERE slug = :slug');

        while (true) {
            $stmt->execute(['slug' => $slug]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }

            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
    }
}
