<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 17 (Bug 2): renomeado de TemaDesafioRepository - a tabela "temas_desafios"
 * virou "temas" (RENAME TABLE, migration 055), agora so' o nivel superior da
 * hierarquia. O nivel "Desafio" (filho de Tema) vive em DesafioRepository.
 */
class TemaRepository
{
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM temas WHERE trilha_id = :trilha_id ORDER BY nome ASC');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function listarAtivosPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM temas WHERE trilha_id = :trilha_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM temas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $tema = $stmt->fetch();

        return $tema !== false ? $tema : null;
    }

    public function criar($trilhaId, $nome, $descricaoLonga, $ativo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO temas (trilha_id, nome, descricao_longa, ativo)
             VALUES (:trilha_id, :nome, :descricao_longa, :ativo)'
        );
        $dados = [
            'trilha_id' => $trilhaId,
            'nome' => $nome,
            'descricao_longa' => $descricaoLonga !== '' ? $descricaoLonga : null,
            'ativo' => $ativo,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'temas', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $nome, $descricaoLonga, $ativo)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE temas SET nome = :nome, descricao_longa = :descricao_longa, ativo = :ativo WHERE id = :id'
        );
        $depois = [
            'nome' => $nome,
            'descricao_longa' => $descricaoLonga !== '' ? $descricaoLonga : null,
            'ativo' => $ativo,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'temas', $id, $antes, $depois);
    }

    /**
     * Remocao real (sem soft-delete) — a FK de "desafios.tema_id" (sem
     * CASCADE) ja protege contra remover um tema com desafios cadastrados.
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM temas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'temas', $id, $antes, null);
    }
}
