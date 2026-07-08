<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

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
        $stmt->execute([
            'etapa_id' => $etapaId,
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'peso' => $peso,
            'escala_min' => $escalaMin,
            'escala_max' => $escalaMax,
            'ordem' => $ordem,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function atualizar($id, $codigo, $nome, $descricao, $peso, $escalaMin, $escalaMax)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE criterios_avaliacao
             SET codigo = :codigo, nome = :nome, descricao = :descricao, peso = :peso,
                 escala_min = :escala_min, escala_max = :escala_max
             WHERE id = :id'
        );
        $stmt->execute([
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'peso' => $peso,
            'escala_min' => $escalaMin,
            'escala_max' => $escalaMax,
            'id' => $id,
        ]);
    }

    public function remover($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM criterios_avaliacao WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function mover($id, $direcao)
    {
        $pdo = Database::conexao();
        $criterio = $this->buscarPorId($id);

        if ($criterio === null) {
            return;
        }

        $operador = $direcao === 'cima' ? '<' : '>';
        $ordenacao = $direcao === 'cima' ? 'DESC' : 'ASC';

        $stmtVizinho = $pdo->prepare(
            "SELECT * FROM criterios_avaliacao
             WHERE etapa_id = :etapa_id AND ordem {$operador} :ordem
             ORDER BY ordem {$ordenacao} LIMIT 1"
        );
        $stmtVizinho->execute(['etapa_id' => $criterio['etapa_id'], 'ordem' => $criterio['ordem']]);
        $vizinho = $stmtVizinho->fetch();

        if ($vizinho === false) {
            return;
        }

        $pdo->beginTransaction();

        try {
            $atualizarOrdem = $pdo->prepare('UPDATE criterios_avaliacao SET ordem = :ordem WHERE id = :id');
            $atualizarOrdem->execute(['ordem' => $vizinho['ordem'], 'id' => $criterio['id']]);
            $atualizarOrdem->execute(['ordem' => $criterio['ordem'], 'id' => $vizinho['id']]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
