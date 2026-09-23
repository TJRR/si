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

    /**
     * Fase 50: renumera pela posicao no array recebido - quem chama
     * (view + JS de arrastar-e-soltar) sempre agrupa por etapa (cada
     * "<ul>" so' contem ids de uma etapa), tanto na tela dedicada de
     * Desempate quanto na secao "Desempate" dentro do resumo de Apuracao
     * (que junta varias etapas na mesma tela, mas em listas separadas).
     * Fase 50 (achado de seguranca): WHERE inclui etapa_id, nao so' id -
     * sem isso, um id de regra de OUTRA etapa seria aceito e teria sua
     * ordem alterada, sem checagem de posse.
     */
    public function reordenar($etapaId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE regras_desempate SET ordem = :ordem WHERE id = :id AND etapa_id = :etapa_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'etapa_id' => $etapaId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'regras_desempate', null, null, ['etapa_id' => $etapaId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
