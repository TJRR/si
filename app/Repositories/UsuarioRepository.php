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

    public function criarAprovadoSemSenha($nome, $email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, status) VALUES (:nome, :email, 'aprovado')"
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function definirSenha($id, $senhaHash)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
        $stmt->execute(['senha_hash' => $senhaHash, 'id' => $id]);
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

    public function listarTodos($concursoId = null)
    {
        $pdo = Database::conexao();

        if ($concursoId === null) {
            return $pdo->query('SELECT * FROM usuarios ORDER BY nome ASC')->fetchAll();
        }

        $stmt = $pdo->prepare(
            'SELECT DISTINCT u.*
             FROM usuarios u
             INNER JOIN usuario_perfil_concurso upc ON upc.usuario_id = u.id
             WHERE upc.concurso_id = :concurso_id
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function atualizarStatus($id, $status)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function atualizarAtivo($id, $ativo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET ativo = :ativo WHERE id = :id');
        $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id]);
    }

    public function perfisDoUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.chave AS perfil, p.nome_exibicao AS perfil_nome, upc.concurso_id AS concurso_id, c.nome AS concurso_nome
             FROM usuario_perfil_concurso upc
             INNER JOIN perfis p ON p.id = upc.perfil_id
             LEFT JOIN concursos c ON c.id = upc.concurso_id
             WHERE upc.usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }
}
