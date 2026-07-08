<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class UsuarioRepository
{
    public function buscarPorEmail($email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $usuario = $stmt->fetch();

        return $usuario !== false ? $usuario : null;
    }

    public function criar($nome, $email, $senhaHash)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, status) VALUES (:nome, :email, :senha_hash, 'pendente')"
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $senhaHash,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function buscarPorGoogleId($googleId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE google_id = :google_id LIMIT 1');
        $stmt->execute(['google_id' => $googleId]);

        $usuario = $stmt->fetch();

        return $usuario !== false ? $usuario : null;
    }

    public function vincularGoogleId($id, $googleId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET google_id = :google_id WHERE id = :id');
        $stmt->execute(['google_id' => $googleId, 'id' => $id]);
    }

    public function criarComGoogle($nome, $email, $googleId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, google_id, status) VALUES (:nome, :email, :google_id, 'pendente')"
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'google_id' => $googleId,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function listarPendentes()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query("SELECT * FROM usuarios WHERE status = 'pendente' ORDER BY criado_em ASC");

        return $stmt->fetchAll();
    }

    public function atualizarStatus($id, $status)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function perfisDoUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.chave AS perfil, upc.concurso_id AS concurso_id
             FROM usuario_perfil_concurso upc
             INNER JOIN perfis p ON p.id = upc.perfil_id
             WHERE upc.usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
