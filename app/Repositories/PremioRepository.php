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
 * concurso. Fase 24: passou a aceitar tambem uma lista por trilha
 * (trilha_id) quando concursos.modo_premiacao = 'por_trilha' - ver
 * listarPorTrilha(). listarPorConcurso() continua servindo so' o modo
 * 'geral' (por isso o filtro trilha_id IS NULL: sem ele, premios
 * cadastrados num modo 'por_trilha' anterior vazariam pra listagem geral
 * se o Admin trocar o modo de volta).
 */
class PremioRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM premios WHERE concurso_id = :concurso_id AND trilha_id IS NULL ORDER BY ordem ASC, id ASC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM premios WHERE trilha_id = :trilha_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['trilha_id' => $trilhaId]);

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

    /**
     * $dados['trilha_id'] (Fase 24) e' null no modo 'geral'; a "proxima
     * ordem" e' calculada dentro do mesmo escopo (trilha OU concurso todo)
     * pra' cada lista comecar sua propria sequencia 0,1,2...
     */
    public function criar($concursoId, array $dados)
    {
        $pdo = Database::conexao();
        $trilhaId = isset($dados['trilha_id']) ? $dados['trilha_id'] : null;

        if ($trilhaId !== null) {
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM premios WHERE trilha_id = :trilha_id');
            $stmt->execute(['trilha_id' => $trilhaId]);
        } else {
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM premios WHERE concurso_id = :concurso_id AND trilha_id IS NULL');
            $stmt->execute(['concurso_id' => $concursoId]);
        }
        $proximaOrdem = (int) $stmt->fetchColumn();

        $campos = $dados + ['concurso_id' => $concursoId, 'trilha_id' => $trilhaId, 'ordem' => $proximaOrdem];

        $stmt = $pdo->prepare(
            'INSERT INTO premios (concurso_id, trilha_id, posicao, descricao, imagem_path, imagem_alt, ordem)
             VALUES (:concurso_id, :trilha_id, :posicao, :descricao, :imagem_path, :imagem_alt, :ordem)'
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
