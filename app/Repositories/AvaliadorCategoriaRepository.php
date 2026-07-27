<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class AvaliadorCategoriaRepository
{
    /**
     * Upsert: cada avaliador tem no maximo 1 categoria por concurso
     * (uq_avaliador_categorias_usuario_concurso).
     */
    public function atribuir($usuarioId, $concursoId, $categoriaAvaliadorId)
    {
        $antes = $this->categoriaDoUsuario($usuarioId, $concursoId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO avaliador_categorias (usuario_id, concurso_id, categoria_avaliador_id)
             VALUES (:usuario_id, :concurso_id, :categoria_id)
             ON DUPLICATE KEY UPDATE categoria_avaliador_id = VALUES(categoria_avaliador_id)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'concurso_id' => $concursoId,
            'categoria_id' => $categoriaAvaliadorId,
        ]);

        Auditoria::registrar('atribuir', 'avaliador_categorias', $usuarioId, $antes, [
            'usuario_id' => $usuarioId,
            'concurso_id' => $concursoId,
            'categoria_avaliador_id' => $categoriaAvaliadorId,
        ]);
    }

    public function categoriaDoUsuario($usuarioId, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ac.*, ca.nome AS categoria_nome
             FROM avaliador_categorias ac
             INNER JOIN categorias_avaliador ca ON ca.id = ac.categoria_avaliador_id
             WHERE ac.usuario_id = :usuario_id AND ac.concurso_id = :concurso_id
             LIMIT 1'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'concurso_id' => $concursoId]);

        $vinculo = $stmt->fetch();

        return $vinculo !== false ? $vinculo : null;
    }

    /**
     * So' traz usuario com perfil "avaliador" ainda vivo em
     * usuario_perfil_concurso (mesmo padrao de
     * PerfilRepository::listarUsuariosPorPerfilConcurso()) e conta
     * aprovada/ativa - evita trazer "avaliador fantasma": alguem cujo
     * vinculo em avaliador_categorias ficou orfao depois que o perfil dele
     * foi trocado na tela Editar usuario (ver AvaliadorCategoriaRepository::
     * removerTodosVinculos(), chamado no momento da troca para nao deixar
     * esse orfao nascer de novo).
     */
    public function listarUsuariosPorCategoria($categoriaAvaliadorId, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT u.*
             FROM usuarios u
             INNER JOIN avaliador_categorias ac ON ac.usuario_id = u.id
             INNER JOIN usuario_perfil_concurso upc ON upc.usuario_id = u.id
             INNER JOIN perfis p ON p.id = upc.perfil_id AND p.chave = \'avaliador\'
             WHERE ac.categoria_avaliador_id = :categoria_id
               AND (upc.concurso_id IS NULL OR upc.concurso_id = :concurso_id)
               AND u.status = \'aprovado\' AND u.ativo = 1
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['categoria_id' => $categoriaAvaliadorId, 'concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    /**
     * Remove todo vinculo de categoria do usuario (todos os concursos) -
     * chamado quando o perfil dele deixa de ser "avaliador"
     * (UsuarioAdminController::salvarEdicao()), ja que a regra do projeto e'
     * um usuario ter no maximo 1 perfil no sistema.
     */
    public function removerTodosVinculos($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM avaliador_categorias WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);

        Auditoria::registrar('remover_todos_vinculos', 'avaliador_categorias', $usuarioId, null, ['usuario_id' => $usuarioId]);
    }
}
