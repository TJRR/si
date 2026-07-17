<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.10) - associacao N:N entre o banco global de perguntas
 * (perguntas_frequentes) e cada concurso: marca quais perguntas estao ativas
 * em qual edicao, sem duplicar o texto ao reaproveitar de uma edicao
 * anterior (so' clona a associacao, nunca a pergunta em si).
 */
class FaqConcursoRepository
{
    /**
     * Todas as perguntas do banco global, com o status de ativacao (se
     * houver) nesta edicao - usado pela tela admin de selecao.
     */
    public function listarComStatusPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT pf.*, fc.id AS associacao_id, fc.ordem AS associacao_ordem, COALESCE(fc.ativo, 0) AS ativo_na_edicao
             FROM perguntas_frequentes pf
             LEFT JOIN faq_concurso fc ON fc.faq_id = pf.id AND fc.concurso_id = :concurso_id
             ORDER BY (fc.id IS NULL) ASC, fc.ordem ASC, pf.categoria ASC, pf.ordem ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function listarAtivasPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT pf.*, fc.ordem AS associacao_ordem
             FROM faq_concurso fc
             INNER JOIN perguntas_frequentes pf ON pf.id = fc.faq_id
             WHERE fc.concurso_id = :concurso_id AND fc.ativo = 1
             ORDER BY fc.ordem ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    /**
     * Ativa a pergunta nesta edicao (cria a associacao se nao existir).
     */
    public function ativar($faqId, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT id FROM faq_concurso WHERE faq_id = :faq_id AND concurso_id = :concurso_id');
        $stmt->execute(['faq_id' => $faqId, 'concurso_id' => $concursoId]);
        $existente = $stmt->fetchColumn();

        if ($existente !== false) {
            $pdo->prepare('UPDATE faq_concurso SET ativo = 1 WHERE id = :id')->execute(['id' => $existente]);
            Auditoria::registrar('ativar', 'faq_concurso', (int) $existente, null, ['faq_id' => $faqId, 'concurso_id' => $concursoId]);
            return;
        }

        $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM faq_concurso WHERE concurso_id = :concurso_id');
        $stmtOrdem->execute(['concurso_id' => $concursoId]);
        $proximaOrdem = (int) $stmtOrdem->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO faq_concurso (faq_id, concurso_id, ordem, ativo) VALUES (:faq_id, :concurso_id, :ordem, 1)'
        );
        $stmt->execute(['faq_id' => $faqId, 'concurso_id' => $concursoId, 'ordem' => $proximaOrdem]);

        Auditoria::registrar('ativar', 'faq_concurso', (int) $pdo->lastInsertId(), null, ['faq_id' => $faqId, 'concurso_id' => $concursoId]);
    }

    public function desativar($faqId, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE faq_concurso SET ativo = 0 WHERE faq_id = :faq_id AND concurso_id = :concurso_id');
        $stmt->execute(['faq_id' => $faqId, 'concurso_id' => $concursoId]);

        Auditoria::registrar('desativar', 'faq_concurso', null, null, ['faq_id' => $faqId, 'concurso_id' => $concursoId]);
    }

    public function reordenar($concursoId, array $faqIds)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE faq_concurso SET ordem = :ordem WHERE faq_id = :faq_id AND concurso_id = :concurso_id');

            foreach ($faqIds as $indice => $faqId) {
                $stmt->execute(['ordem' => $indice, 'faq_id' => (int) $faqId, 'concurso_id' => $concursoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'faq_concurso', null, null, ['concurso_id' => $concursoId, 'faq_ids' => $faqIds]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
