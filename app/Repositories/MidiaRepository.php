<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (4.5) - biblioteca de midia GLOBAL (reaproveitavel entre edicoes),
 * concurso_id so' guarda a origem/filtro opcional, nao restringe uso.
 */
class MidiaRepository
{
    /**
     * Fase 51: a listagem passou a ser por pasta. $pastaId nulo significa a
     * raiz da biblioteca, que e' onde continua tudo o que existia antes das
     * pastas (a coluna nasce nula).
     */
    public function listar($tipo = null, $pastaId = null)
    {
        $pdo = Database::conexao();
        $condicoes = [$pastaId === null ? 'pasta_id IS NULL' : 'pasta_id = :pasta_id'];
        $parametros = [];

        if ($pastaId !== null) {
            $parametros['pasta_id'] = $pastaId;
        }

        if ($tipo !== null) {
            $condicoes[] = 'tipo = :tipo';
            $parametros['tipo'] = $tipo;
        }

        $stmt = $pdo->prepare('SELECT * FROM midias WHERE ' . implode(' AND ', $condicoes) . ' ORDER BY criado_em DESC');
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function moverPara($id, $pastaId)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE midias SET pasta_id = :pasta_id WHERE id = :id');
        $stmt->execute(['pasta_id' => $pastaId, 'id' => $id]);

        Auditoria::registrar('atualizar', 'midias', $id, $antes, ['pasta_id' => $pastaId]);
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM midias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $midia = $stmt->fetch();

        return $midia !== false ? $midia : null;
    }

    public function criar(array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO midias (concurso_id, pasta_id, arquivo_path, tipo, alt_text, titulo, descricao, criado_por)
             VALUES (:concurso_id, :pasta_id, :arquivo_path, :tipo, :alt_text, :titulo, :descricao, :criado_por)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'midias', $id, null, $dados);

        return $id;
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM midias WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'midias', $id, $antes, null);
    }
}
