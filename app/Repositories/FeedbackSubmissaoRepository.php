<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 17 (Melhoria 1): feedback do avaliador por submissao inteira (modo
 * "submissao" de etapas.modo_feedback_avaliador) - 1 texto por avaliador por
 * submissao, mesmo modelo de NotaLancadaRepository.
 */
class FeedbackSubmissaoRepository
{
    public function buscarPorSubmissaoEUsuario($submissaoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM feedback_submissao WHERE submissao_id = :submissao_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['submissao_id' => $submissaoId, 'usuario_id' => $usuarioId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function listarPorSubmissao($submissaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM feedback_submissao WHERE submissao_id = :submissao_id ORDER BY id ASC');
        $stmt->execute(['submissao_id' => $submissaoId]);

        return $stmt->fetchAll();
    }

    public function salvar($submissaoId, $usuarioId, $feedback)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO feedback_submissao (submissao_id, usuario_id, feedback)
             VALUES (:submissao_id, :usuario_id, :feedback)
             ON DUPLICATE KEY UPDATE feedback = :feedback_atualizado'
        );
        $stmt->execute([
            'submissao_id' => $submissaoId,
            'usuario_id' => $usuarioId,
            'feedback' => $feedback,
            'feedback_atualizado' => $feedback,
        ]);

        Auditoria::registrar('salvar', 'feedback_submissao', $submissaoId, null, [
            'usuario_id' => $usuarioId,
            'feedback' => $feedback,
        ]);
    }
}
