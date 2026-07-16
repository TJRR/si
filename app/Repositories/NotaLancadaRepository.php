<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class NotaLancadaRepository
{
    public function listarPorSubmissao($submissaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM notas_lancadas WHERE submissao_id = :submissao_id ORDER BY criterio_avaliacao_id ASC'
        );
        $stmt->execute(['submissao_id' => $submissaoId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 17 (Bug 5): igual listarPorSubmissao(), mas com JOIN pra trazer o
     * nome do critério e do avaliador - usado no popup "Ver avaliações" do
     * Admin (que vê tudo, sem sigilo de identidade de avaliador).
     */
    public function listarPorSubmissaoComDetalhes($submissaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT n.*, c.nome AS criterio_nome, u.nome AS usuario_nome
             FROM notas_lancadas n
             INNER JOIN criterios_avaliacao c ON c.id = n.criterio_avaliacao_id
             INNER JOIN usuarios u ON u.id = n.usuario_id
             WHERE n.submissao_id = :submissao_id
             ORDER BY u.nome ASC, c.ordem ASC, c.id ASC'
        );
        $stmt->execute(['submissao_id' => $submissaoId]);

        return $stmt->fetchAll();
    }

    public function listarPorSubmissaoEUsuario($submissaoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM notas_lancadas
             WHERE submissao_id = :submissao_id AND usuario_id = :usuario_id
             ORDER BY criterio_avaliacao_id ASC'
        );
        $stmt->execute(['submissao_id' => $submissaoId, 'usuario_id' => $usuarioId]);

        $notas = [];
        foreach ($stmt->fetchAll() as $linha) {
            $notas[(int) $linha['criterio_avaliacao_id']] = $linha;
        }

        return $notas;
    }

    /**
     * $feedback (Fase 17, Melhoria 1) e' opcional - so' usado quando a etapa
     * esta em modo_feedback_avaliador = 'criterio'; null preserva o
     * comportamento anterior (so' nota, sem feedback por criterio).
     */
    public function salvar($submissaoId, $criterioAvaliacaoId, $usuarioId, $nota, $feedback = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO notas_lancadas (submissao_id, criterio_avaliacao_id, usuario_id, nota, feedback)
             VALUES (:submissao_id, :criterio_avaliacao_id, :usuario_id, :nota, :feedback)
             ON DUPLICATE KEY UPDATE nota = :nota_atualizada, feedback = :feedback_atualizado'
        );
        $stmt->execute([
            'submissao_id' => $submissaoId,
            'criterio_avaliacao_id' => $criterioAvaliacaoId,
            'usuario_id' => $usuarioId,
            'nota' => $nota,
            'feedback' => $feedback,
            'nota_atualizada' => $nota,
            'feedback_atualizado' => $feedback,
        ]);

        Auditoria::registrar('salvar', 'notas_lancadas', $submissaoId, null, [
            'criterio_avaliacao_id' => $criterioAvaliacaoId,
            'usuario_id' => $usuarioId,
            'nota' => $nota,
            'feedback' => $feedback,
        ]);
    }

    public function contarNotasPorUsuario($submissaoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(DISTINCT criterio_avaliacao_id) FROM notas_lancadas
             WHERE submissao_id = :submissao_id AND usuario_id = :usuario_id'
        );
        $stmt->execute(['submissao_id' => $submissaoId, 'usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }
}
