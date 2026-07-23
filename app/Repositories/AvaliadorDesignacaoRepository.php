<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class AvaliadorDesignacaoRepository
{
    public function listarPorSubmissao($submissaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ad.*, u.nome AS usuario_nome, ca.nome AS categoria_nome
             FROM avaliador_designacoes ad
             INNER JOIN usuarios u ON u.id = ad.usuario_id
             INNER JOIN submissoes s ON s.id = ad.submissao_id
             INNER JOIN etapas e ON e.id = s.etapa_id
             INNER JOIN trilhas t ON t.id = e.trilha_id
             LEFT JOIN avaliador_categorias ac ON ac.usuario_id = ad.usuario_id AND ac.concurso_id = t.concurso_id
             LEFT JOIN categorias_avaliador ca ON ca.id = ac.categoria_avaliador_id
             WHERE ad.submissao_id = :submissao_id
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['submissao_id' => $submissaoId]);

        return $stmt->fetchAll();
    }

    public function listarSubmissoesDesignadasNaEtapa($usuarioId, $etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT s.id
             FROM avaliador_designacoes ad
             INNER JOIN submissoes s ON s.id = ad.submissao_id
             WHERE ad.usuario_id = :usuario_id AND s.etapa_id = :etapa_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'etapa_id' => $etapaId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function existeDesignacao($submissaoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM avaliador_designacoes WHERE submissao_id = :submissao_id AND usuario_id = :usuario_id'
        );
        $stmt->execute(['submissao_id' => $submissaoId, 'usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function contarPorSubmissao($submissaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM avaliador_designacoes WHERE submissao_id = :submissao_id');
        $stmt->execute(['submissao_id' => $submissaoId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fase 20 (#118): total de designacoes (par submissao+avaliador) da
     * etapa e quantas ja estao completas (avaliador lancou nota em todos
     * os criterios) - usado no quadro de progresso do painel do Admin.
     */
    public function progressoPorEtapa($etapaId, $totalCriterios)
    {
        $pdo = Database::conexao();

        $stmtTotal = $pdo->prepare(
            'SELECT COUNT(*)
             FROM avaliador_designacoes ad
             INNER JOIN submissoes s ON s.id = ad.submissao_id
             WHERE s.etapa_id = :etapa_id'
        );
        $stmtTotal->execute(['etapa_id' => $etapaId]);
        $total = (int) $stmtTotal->fetchColumn();

        $stmtCompletas = $pdo->prepare(
            'SELECT COUNT(*) FROM (
                SELECT ad.id
                FROM avaliador_designacoes ad
                INNER JOIN submissoes s ON s.id = ad.submissao_id
                LEFT JOIN notas_lancadas nl ON nl.submissao_id = ad.submissao_id AND nl.usuario_id = ad.usuario_id
                WHERE s.etapa_id = :etapa_id
                GROUP BY ad.id
                HAVING COUNT(DISTINCT nl.criterio_avaliacao_id) >= :total_criterios
             ) completas'
        );
        $stmtCompletas->execute(['etapa_id' => $etapaId, 'total_criterios' => $totalCriterios]);
        $completas = (int) $stmtCompletas->fetchColumn();

        return ['total' => $total, 'completas' => $completas];
    }

    public function contarPorUsuarioNaEtapa($usuarioId, $etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM avaliador_designacoes ad
             INNER JOIN submissoes s ON s.id = ad.submissao_id
             WHERE ad.usuario_id = :usuario_id AND s.etapa_id = :etapa_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'etapa_id' => $etapaId]);

        return (int) $stmt->fetchColumn();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM avaliador_designacoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $designacao = $stmt->fetch();

        return $designacao !== false ? $designacao : null;
    }

    /**
     * $origem (Fase 17, Bug 3): 'sorteio' quando vem de
     * AvaliadorDesignacaoService::confirmarDistribuicao() - essas designacoes
     * nunca podem ser removidas (ver DesignacaoAdminController::remover()).
     */
    public function criar($submissaoId, $usuarioId, $atribuidoPor = null, $origem = 'manual')
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO avaliador_designacoes (submissao_id, usuario_id, atribuido_por, origem)
             VALUES (:submissao_id, :usuario_id, :atribuido_por, :origem)'
        );
        $dados = [
            'submissao_id' => $submissaoId,
            'usuario_id' => $usuarioId,
            'atribuido_por' => $atribuidoPor,
            'origem' => $origem,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'avaliador_designacoes', $id, null, $dados);
    }

    public function remover($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM avaliador_designacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'avaliador_designacoes', $id, null, null);
    }
}
