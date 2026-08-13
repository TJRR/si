<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 29 (Tira-Duvidas): historico de escalonamentos (quem escalou pra
 * quem e quando) - mesmo padrao "quem/pra quem/quando" de
 * avaliador_designacoes, mas sem UNIQUE KEY porque aqui e' historico
 * acumulado (uma duvida pode ser escalada varias vezes), nao vinculo atual.
 */
class DuvidaEscalonamentoRepository
{
    public function criar($duvidaId, $deUsuarioId, $paraUsuarioId)
    {
        $pdo = Database::conexao();
        $dados = [
            'duvida_id' => $duvidaId,
            'de_usuario_id' => $deUsuarioId,
            'para_usuario_id' => $paraUsuarioId,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO duvida_escalonamentos (duvida_id, de_usuario_id, para_usuario_id)
             VALUES (:duvida_id, :de_usuario_id, :para_usuario_id)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'duvida_escalonamentos', $id, null, $dados);

        return $id;
    }

    public function listarPorDuvida($duvidaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT de.*, ud.nome AS de_usuario_nome, up.nome AS para_usuario_nome
             FROM duvida_escalonamentos de
             INNER JOIN usuarios ud ON ud.id = de.de_usuario_id
             INNER JOIN usuarios up ON up.id = de.para_usuario_id
             WHERE de.duvida_id = :duvida_id
             ORDER BY de.criado_em ASC'
        );
        $stmt->execute(['duvida_id' => $duvidaId]);

        return $stmt->fetchAll();
    }
}
