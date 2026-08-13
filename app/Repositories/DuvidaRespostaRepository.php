<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 29 (Tira-Duvidas): historico de respostas de uma duvida - mais de
 * uma ao longo do tempo por causa de reabertura, nunca sobrescreve.
 */
class DuvidaRespostaRepository
{
    public function criar($duvidaId, $usuarioId, $resposta, $anexoPath, $anexoNomeOriginal)
    {
        $pdo = Database::conexao();
        $dados = [
            'duvida_id' => $duvidaId,
            'usuario_id' => $usuarioId,
            'resposta' => $resposta,
            'anexo_path' => $anexoPath,
            'anexo_nome_original' => $anexoNomeOriginal,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO duvida_respostas (duvida_id, usuario_id, resposta, anexo_path, anexo_nome_original)
             VALUES (:duvida_id, :usuario_id, :resposta, :anexo_path, :anexo_nome_original)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'duvida_respostas', $id, null, $dados);

        return $id;
    }

    public function listarPorDuvida($duvidaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT dr.*, u.nome AS usuario_nome, u.foto_path AS usuario_foto_path
             FROM duvida_respostas dr
             INNER JOIN usuarios u ON u.id = dr.usuario_id
             WHERE dr.duvida_id = :duvida_id
             ORDER BY dr.criado_em ASC'
        );
        $stmt->execute(['duvida_id' => $duvidaId]);

        return $stmt->fetchAll();
    }
}
