<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class CategoriaAvaliadorRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM categorias_avaliador WHERE concurso_id = :concurso_id ORDER BY nome ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM categorias_avaliador WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $categoria = $stmt->fetch();

        return $categoria !== false ? $categoria : null;
    }

    public function nomeJaExisteNoConcurso($concursoId, $nome, $exceptoId = null)
    {
        $pdo = Database::conexao();
        $sql = 'SELECT COUNT(*) FROM categorias_avaliador WHERE concurso_id = :concurso_id AND nome = :nome';
        $parametros = ['concurso_id' => $concursoId, 'nome' => $nome];

        if ($exceptoId !== null) {
            $sql .= ' AND id != :excetoId';
            $parametros['excetoId'] = $exceptoId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar($concursoId, $nome)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO categorias_avaliador (concurso_id, nome) VALUES (:concurso_id, :nome)'
        );
        $dados = ['concurso_id' => $concursoId, 'nome' => $nome];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'categorias_avaliador', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $nome)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE categorias_avaliador SET nome = :nome WHERE id = :id');
        $stmt->execute(['nome' => $nome, 'id' => $id]);

        Auditoria::registrar('atualizar', 'categorias_avaliador', $id, $antes, ['nome' => $nome]);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM categorias_avaliador WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'categorias_avaliador', $id, $antes, null);
    }
}
