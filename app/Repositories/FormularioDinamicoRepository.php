<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class FormularioDinamicoRepository
{
    public function listar($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM formularios_dinamicos WHERE concurso_id = :concurso_id ORDER BY nome ASC, versao ASC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM formularios_dinamicos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $formulario = $stmt->fetch();

        return $formulario !== false ? $formulario : null;
    }

    public function criar($concursoId, $nome, $descricao, $versao = 1, $status = 'rascunho')
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO formularios_dinamicos (concurso_id, nome, descricao, versao, status) VALUES (:concurso_id, :nome, :descricao, :versao, :status)'
        );
        $stmt->execute([
            'concurso_id' => $concursoId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'versao' => $versao,
            'status' => $status,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function atualizar($id, $nome, $descricao)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE formularios_dinamicos SET nome = :nome, descricao = :descricao WHERE id = :id');
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'id' => $id,
        ]);
    }

    public function atualizarStatus($id, $status)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE formularios_dinamicos SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
