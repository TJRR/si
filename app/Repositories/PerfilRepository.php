<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class PerfilRepository
{
    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM perfis ORDER BY nome_exibicao ASC')->fetchAll();
    }

    public function buscarPorChave($chave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM perfis WHERE chave = :chave LIMIT 1');
        $stmt->execute(['chave' => $chave]);

        $perfil = $stmt->fetch();

        return $perfil !== false ? $perfil : null;
    }

    public function atribuir($usuarioId, $perfilId, $concursoId = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO usuario_perfil_concurso (usuario_id, perfil_id, concurso_id) VALUES (:usuario_id, :perfil_id, :concurso_id)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'perfil_id' => $perfilId,
            'concurso_id' => $concursoId,
        ]);

        Auditoria::registrar('atribuir', 'usuario_perfil_concurso', $usuarioId, null, [
            'perfil_id' => $perfilId,
            'concurso_id' => $concursoId,
        ]);
    }

    /**
     * Substitui o(s) vinculo(s) de perfil do usuario por um unico novo -
     * regra do projeto: um usuario tem no maximo 1 perfil no sistema. Usada
     * pela tela "Editar usuario" (Admin), que oferece so um select de perfil,
     * nao uma lista de multiplos vinculos.
     */
    public function substituirPerfil($usuarioId, $perfilId, $concursoId = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM usuario_perfil_concurso WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);

        $this->atribuir($usuarioId, $perfilId, $concursoId);
    }

    public function possuiPerfil($usuarioId, $perfilId, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM usuario_perfil_concurso
             WHERE usuario_id = :usuario_id AND perfil_id = :perfil_id
               AND (concurso_id <=> :concurso_id)'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'perfil_id' => $perfilId, 'concurso_id' => $concursoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function listarUsuariosPorPerfilConcurso($perfilChave, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT u.*
             FROM usuarios u
             INNER JOIN usuario_perfil_concurso upc ON upc.usuario_id = u.id
             INNER JOIN perfis p ON p.id = upc.perfil_id
             WHERE p.chave = :chave AND (upc.concurso_id IS NULL OR upc.concurso_id = :concurso_id)
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['chave' => $perfilChave, 'concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }
}
