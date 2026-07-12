<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class ParticipanteRepository
{
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM participantes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $participante = $stmt->fetch();

        return $participante !== false ? $participante : null;
    }

    public function buscarPorCpf($cpf)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM participantes WHERE cpf = :cpf LIMIT 1');
        $stmt->execute(['cpf' => $cpf]);

        $participante = $stmt->fetch();

        return $participante !== false ? $participante : null;
    }

    public function buscarPorEmail($email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM participantes WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $participante = $stmt->fetch();

        return $participante !== false ? $participante : null;
    }

    public function criar($nome, $cpf, $email, $telefone, $vinculoProfissao)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO participantes (nome, cpf, email, telefone, vinculo_profissao)
             VALUES (:nome, :cpf, :email, :telefone, :vinculo_profissao)'
        );
        $stmt->execute([
            'nome' => $nome,
            'cpf' => $cpf !== '' ? $cpf : null,
            'email' => $email !== '' ? $email : null,
            'telefone' => $telefone !== '' ? $telefone : null,
            'vinculo_profissao' => $vinculoProfissao !== '' ? $vinculoProfissao : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function atualizarCpf($id, $cpf)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE participantes SET cpf = :cpf WHERE id = :id');
        $stmt->execute(['cpf' => $cpf, 'id' => $id]);
    }

    public function atualizarDados($id, $nome, $telefone, $cpf)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE participantes SET nome = :nome, telefone = :telefone, cpf = :cpf WHERE id = :id'
        );
        $stmt->execute([
            'nome' => $nome,
            'telefone' => $telefone !== '' ? $telefone : null,
            'cpf' => $cpf !== '' ? $cpf : null,
            'id' => $id,
        ]);
    }
}
