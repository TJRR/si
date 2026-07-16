<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class UsuarioRepository
{
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $usuario = $stmt->fetch();

        return $usuario !== false ? $usuario : null;
    }

    /**
     * Fase 17 (Melhoria 2): usuarios elegiveis para "visualizar como" -
     * ativos, ja aprovados, e sem perfil administrador (decisao do usuario:
     * um Admin nao pode "virar" outro Admin).
     */
    public function listarAtivosNaoAdministradores()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query(
            "SELECT u.* FROM usuarios u
             WHERE u.status = 'aprovado' AND u.ativo = 1
               AND u.id NOT IN (
                   SELECT upc.usuario_id
                   FROM usuario_perfil_concurso upc
                   INNER JOIN perfis p ON p.id = upc.perfil_id
                   WHERE p.chave = 'administrador'
               )
             ORDER BY u.nome ASC"
        );

        return $stmt->fetchAll();
    }

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
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'usuarios', $id, null, ['nome' => $nome, 'email' => $email, 'status' => 'pendente']);

        return $id;
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
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET google_id = :google_id WHERE id = :id');
        $stmt->execute(['google_id' => $googleId, 'id' => $id]);

        Auditoria::registrar('vincular_google', 'usuarios', $id, $antes, ['google_id' => $googleId]);
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
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'usuarios', $id, null, ['nome' => $nome, 'email' => $email, 'status' => 'aprovado']);

        return $id;
    }

    public function definirSenha($id, $senhaHash)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
        $stmt->execute(['senha_hash' => $senhaHash, 'id' => $id]);

        Auditoria::registrar('definir_senha', 'usuarios', $id, ['senha_hash' => $antes !== null ? '(hash anterior omitido)' : null], ['senha_hash' => '(hash omitido)']);
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
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'usuarios', $id, null, ['nome' => $nome, 'email' => $email, 'status' => 'pendente']);

        return $id;
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
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        Auditoria::registrar('atualizar_status', 'usuarios', $id, $antes, ['status' => $status]);
    }

    public function atualizarAtivo($id, $ativo)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET ativo = :ativo WHERE id = :id');
        $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id]);

        Auditoria::registrar('atualizar_ativo', 'usuarios', $id, $antes, ['ativo' => $ativo ? 1 : 0]);
    }

    public function perfisDoUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT upc.id AS vinculo_id, p.chave AS perfil, p.nome_exibicao AS perfil_nome,
                    upc.concurso_id AS concurso_id, c.nome AS concurso_nome
             FROM usuario_perfil_concurso upc
             INNER JOIN perfis p ON p.id = upc.perfil_id
             LEFT JOIN concursos c ON c.id = upc.concurso_id
             WHERE upc.usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function atualizarNome($id, $nome)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET nome = :nome WHERE id = :id');
        $stmt->execute(['nome' => $nome, 'id' => $id]);

        Auditoria::registrar('atualizar_nome', 'usuarios', $id, $antes, ['nome' => $nome]);
    }

    public function atualizarFoto($id, $caminhoRelativo)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET foto_path = :foto_path WHERE id = :id');
        $stmt->execute(['foto_path' => $caminhoRelativo, 'id' => $id]);

        Auditoria::registrar('atualizar_foto', 'usuarios', $id, $antes, ['foto_path' => $caminhoRelativo]);
    }
}
