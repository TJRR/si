<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Core\Texto;

class TrilhaRepository
{
    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trilhas WHERE concurso_id = :concurso_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trilhas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $trilha = $stmt->fetch();

        return $trilha !== false ? $trilha : null;
    }

    public function buscarPorNome($nome)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trilhas WHERE nome = :nome LIMIT 1');
        $stmt->execute(['nome' => $nome]);

        $trilha = $stmt->fetch();

        return $trilha !== false ? $trilha : null;
    }

    public function criar($concursoId, $nome, $descricao, $ordem, $ativo, $minimoIntegrantesHomologados = 1)
    {
        $pdo = Database::conexao();
        $slug = $this->gerarSlugUnico($concursoId, $nome);

        $stmt = $pdo->prepare(
            'INSERT INTO trilhas (concurso_id, nome, slug, descricao, ordem, ativo, minimo_integrantes_homologados)
             VALUES (:concurso_id, :nome, :slug, :descricao, :ordem, :ativo, :minimo_integrantes_homologados)'
        );
        $dados = [
            'concurso_id' => $concursoId,
            'nome' => $nome,
            'slug' => $slug,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'ativo' => $ativo,
            'minimo_integrantes_homologados' => $minimoIntegrantesHomologados,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trilhas', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $nome, $descricao, $ordem, $ativo, $minimoIntegrantesHomologados = 1)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'UPDATE trilhas SET nome = :nome, descricao = :descricao, ordem = :ordem, ativo = :ativo,
                minimo_integrantes_homologados = :minimo_integrantes_homologados
             WHERE id = :id'
        );
        $depois = [
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'ativo' => $ativo,
            'minimo_integrantes_homologados' => $minimoIntegrantesHomologados,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'trilhas', $id, $antes, $depois);
    }

    /**
     * Remocao real (sem soft-delete) — ver EtapaRepository::remover() para a
     * explicacao de por que a FK (sem CASCADE) ja protege contra remover uma
     * trilha com etapas/equipes/formulas/regras de desempate vinculadas.
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM trilhas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'trilhas', $id, $antes, null);
    }

    private function gerarSlugUnico($concursoId, $nome)
    {
        $pdo = Database::conexao();
        $base = Texto::slugify($nome);
        $slug = $base;
        $sufixo = 2;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM trilhas WHERE concurso_id = :concurso_id AND slug = :slug');

        while (true) {
            $stmt->execute(['concurso_id' => $concursoId, 'slug' => $slug]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }

            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
    }
}
