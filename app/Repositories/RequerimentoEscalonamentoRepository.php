<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 30: historico de escalonamentos de um requerimento - identico ao
 * padrao de duvida_escalonamentos (Fase 29): historico acumulado, sem
 * UNIQUE KEY (um requerimento pode ser escalado varias vezes).
 */
class RequerimentoEscalonamentoRepository
{
    public function criar($requerimentoId, $deUsuarioId, $paraUsuarioId)
    {
        $pdo = Database::conexao();
        $dados = [
            'requerimento_id' => $requerimentoId,
            'de_usuario_id' => $deUsuarioId,
            'para_usuario_id' => $paraUsuarioId,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO requerimento_escalonamentos (requerimento_id, de_usuario_id, para_usuario_id)
             VALUES (:requerimento_id, :de_usuario_id, :para_usuario_id)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'requerimento_escalonamentos', $id, null, $dados);

        return $id;
    }

    public function listarPorRequerimento($requerimentoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT re.*, ud.nome AS de_usuario_nome, up.nome AS para_usuario_nome
             FROM requerimento_escalonamentos re
             INNER JOIN usuarios ud ON ud.id = re.de_usuario_id
             INNER JOIN usuarios up ON up.id = re.para_usuario_id
             WHERE re.requerimento_id = :requerimento_id
             ORDER BY re.criado_em ASC'
        );
        $stmt->execute(['requerimento_id' => $requerimentoId]);

        return $stmt->fetchAll();
    }
}
