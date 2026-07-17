<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.7 Premiacao) - lista estruturada de colocacoes/premios por
 * concurso.
 */
class PremioRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM premios WHERE concurso_id = :concurso_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM premios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $premio = $stmt->fetch();

        return $premio !== false ? $premio : null;
    }

    public function criar($concursoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM premios WHERE concurso_id = :concurso_id');
        $stmt->execute(['concurso_id' => $concursoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $campos = $dados + ['concurso_id' => $concursoId, 'ordem' => $proximaOrdem];

        $stmt = $pdo->prepare(
            'INSERT INTO premios (concurso_id, posicao, descricao, imagem_path, imagem_alt, ordem)
             VALUES (:concurso_id, :posicao, :descricao, :imagem_path, :imagem_alt, :ordem)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'premios', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE premios SET posicao = :posicao, descricao = :descricao, imagem_path = :imagem_path, imagem_alt = :imagem_alt WHERE id = :id'
        );
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'premios', $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM premios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'premios', $id, $antes, null);
    }

    public function reordenar($concursoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE premios SET ordem = :ordem WHERE id = :id AND concurso_id = :concurso_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'concurso_id' => $concursoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'premios', null, null, ['concurso_id' => $concursoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
