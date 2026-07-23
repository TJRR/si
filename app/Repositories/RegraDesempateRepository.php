<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class RegraDesempateRepository
{
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT rd.*, ca.nome AS criterio_nome, e.nome AS etapa_nome
             FROM regras_desempate rd
             INNER JOIN etapas e ON e.id = rd.etapa_id
             LEFT JOIN criterios_avaliacao ca ON ca.id = rd.criterio_avaliacao_id
             WHERE rd.trilha_id = :trilha_id
             ORDER BY e.ordem ASC, rd.ordem ASC, rd.id ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function listarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT rd.*, ca.nome AS criterio_nome
             FROM regras_desempate rd
             LEFT JOIN criterios_avaliacao ca ON ca.id = rd.criterio_avaliacao_id
             WHERE rd.etapa_id = :etapa_id
             ORDER BY rd.ordem ASC, rd.id ASC'
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    public function listarCriteriosDisponiveisPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ca.id, ca.nome AS criterio_nome, e.nome AS etapa_nome, e.ordem AS etapa_ordem
             FROM criterios_avaliacao ca
             INNER JOIN etapas e ON e.id = ca.etapa_id
             WHERE e.trilha_id = :trilha_id
             ORDER BY e.ordem ASC, ca.ordem ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function listarCriteriosDisponiveisPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT id, nome AS criterio_nome FROM criterios_avaliacao WHERE etapa_id = :etapa_id ORDER BY ordem ASC'
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM regras_desempate WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $regra = $stmt->fetch();

        return $regra !== false ? $regra : null;
    }

    public function criar($trilhaId, $etapaId, $tipo, $criterioAvaliacaoId, $direcao)
    {
        $pdo = Database::conexao();

        $stmtOrdem = $pdo->prepare(
            'SELECT COALESCE(MAX(ordem), 0) + 1 FROM regras_desempate WHERE trilha_id = :trilha_id AND etapa_id = :etapa_id'
        );
        $stmtOrdem->execute(['trilha_id' => $trilhaId, 'etapa_id' => $etapaId]);
        $ordem = (int) $stmtOrdem->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO regras_desempate (trilha_id, etapa_id, tipo, ordem, criterio_avaliacao_id, direcao)
             VALUES (:trilha_id, :etapa_id, :tipo, :ordem, :criterio_avaliacao_id, :direcao)'
        );
        $dados = [
            'trilha_id' => $trilhaId,
            'etapa_id' => $etapaId,
            'tipo' => $tipo,
            'ordem' => $ordem,
            'criterio_avaliacao_id' => $criterioAvaliacaoId,
            'direcao' => $direcao,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'regras_desempate', $id, null, $dados);

        return $id;
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM regras_desempate WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'regras_desempate', $id, $antes, null);
    }

    public function mover($id, $direcao)
    {
        $pdo = Database::conexao();
        $regra = $this->buscarPorId($id);

        if ($regra === null) {
            return;
        }

        $operador = $direcao === 'cima' ? '<' : '>';
        $ordenacao = $direcao === 'cima' ? 'DESC' : 'ASC';

        $stmtVizinho = $pdo->prepare(
            "SELECT * FROM regras_desempate
             WHERE trilha_id = :trilha_id AND etapa_id = :etapa_id AND ordem {$operador} :ordem
             ORDER BY ordem {$ordenacao} LIMIT 1"
        );
        $stmtVizinho->execute(['trilha_id' => $regra['trilha_id'], 'etapa_id' => $regra['etapa_id'], 'ordem' => $regra['ordem']]);
        $vizinho = $stmtVizinho->fetch();

        if ($vizinho === false) {
            return;
        }

        $pdo->beginTransaction();

        try {
            $atualizarOrdem = $pdo->prepare('UPDATE regras_desempate SET ordem = :ordem WHERE id = :id');
            $atualizarOrdem->execute(['ordem' => $vizinho['ordem'], 'id' => $regra['id']]);
            $atualizarOrdem->execute(['ordem' => $regra['ordem'], 'id' => $vizinho['id']]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('mover', 'regras_desempate', $id, ['ordem' => $regra['ordem']], ['ordem' => $vizinho['ordem'], 'trocado_com_id' => $vizinho['id']]);
    }
}
