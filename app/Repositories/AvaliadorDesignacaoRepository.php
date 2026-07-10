<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class AvaliadorDesignacaoRepository
{
    public function listarPorSubmissao($submissaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ad.*, u.nome AS usuario_nome
             FROM avaliador_designacoes ad
             INNER JOIN usuarios u ON u.id = ad.usuario_id
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

    public function criar($submissaoId, $usuarioId, $atribuidoPor = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO avaliador_designacoes (submissao_id, usuario_id, atribuido_por)
             VALUES (:submissao_id, :usuario_id, :atribuido_por)'
        );
        $stmt->execute([
            'submissao_id' => $submissaoId,
            'usuario_id' => $usuarioId,
            'atribuido_por' => $atribuidoPor,
        ]);
    }

    public function remover($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM avaliador_designacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
