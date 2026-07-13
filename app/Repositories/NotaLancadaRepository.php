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

    public function salvar($submissaoId, $criterioAvaliacaoId, $usuarioId, $nota)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO notas_lancadas (submissao_id, criterio_avaliacao_id, usuario_id, nota)
             VALUES (:submissao_id, :criterio_avaliacao_id, :usuario_id, :nota)
             ON DUPLICATE KEY UPDATE nota = :nota_atualizada'
        );
        $stmt->execute([
            'submissao_id' => $submissaoId,
            'criterio_avaliacao_id' => $criterioAvaliacaoId,
            'usuario_id' => $usuarioId,
            'nota' => $nota,
            'nota_atualizada' => $nota,
        ]);

        Auditoria::registrar('salvar', 'notas_lancadas', $submissaoId, null, [
            'criterio_avaliacao_id' => $criterioAvaliacaoId,
            'usuario_id' => $usuarioId,
            'nota' => $nota,
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
