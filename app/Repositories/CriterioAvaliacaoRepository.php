<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class CriterioAvaliacaoRepository
{
    public function listarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM criterios_avaliacao WHERE etapa_id = :etapa_id ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM criterios_avaliacao WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $criterio = $stmt->fetch();

        return $criterio !== false ? $criterio : null;
    }

    public function somaPesosPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(peso), 0) FROM criterios_avaliacao WHERE etapa_id = :etapa_id');
        $stmt->execute(['etapa_id' => $etapaId]);

        return (float) $stmt->fetchColumn();
    }

    public function contarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM criterios_avaliacao WHERE etapa_id = :etapa_id');
        $stmt->execute(['etapa_id' => $etapaId]);

        return (int) $stmt->fetchColumn();
    }

    public function codigoJaExisteNaEtapa($etapaId, $codigo, $exceptoId = null)
    {
        $pdo = Database::conexao();
        $sql = 'SELECT COUNT(*) FROM criterios_avaliacao WHERE etapa_id = :etapa_id AND codigo = :codigo';
        $parametros = ['etapa_id' => $etapaId, 'codigo' => $codigo];

        if ($exceptoId !== null) {
            $sql .= ' AND id != :excetoId';
            $parametros['excetoId'] = $exceptoId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar($etapaId, $codigo, $nome, $descricao, $peso, $escalaMin, $escalaMax)
    {
        $pdo = Database::conexao();

        $stmtOrdem = $pdo->prepare(
            'SELECT COALESCE(MAX(ordem), 0) + 1 FROM criterios_avaliacao WHERE etapa_id = :etapa_id'
        );
        $stmtOrdem->execute(['etapa_id' => $etapaId]);
        $ordem = (int) $stmtOrdem->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO criterios_avaliacao (etapa_id, codigo, nome, descricao, peso, escala_min, escala_max, ordem)
             VALUES (:etapa_id, :codigo, :nome, :descricao, :peso, :escala_min, :escala_max, :ordem)'
        );
        $dados = [
            'etapa_id' => $etapaId,
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'peso' => $peso,
            'escala_min' => $escalaMin,
            'escala_max' => $escalaMax,
            'ordem' => $ordem,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'criterios_avaliacao', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $codigo, $nome, $descricao, $peso, $escalaMin, $escalaMax)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE criterios_avaliacao
             SET codigo = :codigo, nome = :nome, descricao = :descricao, peso = :peso,
                 escala_min = :escala_min, escala_max = :escala_max
             WHERE id = :id'
        );
        $depois = [
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'peso' => $peso,
            'escala_min' => $escalaMin,
            'escala_max' => $escalaMax,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'criterios_avaliacao', $id, $antes, $depois);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM criterios_avaliacao WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'criterios_avaliacao', $id, $antes, null);
    }

    /**
     * Fase 50 (achado de seguranca): WHERE inclui etapa_id, nao so' id -
     * sem isso, um id de criterio de OUTRA etapa seria aceito e teria sua
     * ordem alterada, sem checagem de posse.
     */
    public function reordenar($etapaId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE criterios_avaliacao SET ordem = :ordem WHERE id = :id AND etapa_id = :etapa_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'etapa_id' => $etapaId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'criterios_avaliacao', null, null, ['etapa_id' => $etapaId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
