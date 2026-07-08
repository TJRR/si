<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

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
    }
}
