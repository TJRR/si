<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class TokenSenhaRepository
{
    public function criar($usuarioId, $tipo, $validadeHoras = 72)
    {
        $pdo = Database::conexao();
        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            'INSERT INTO tokens_senha (usuario_id, token, tipo, expira_em)
             VALUES (:usuario_id, :token, :tipo, DATE_ADD(NOW(), INTERVAL :horas HOUR))'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'token' => $token,
            'tipo' => $tipo,
            'horas' => $validadeHoras,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'tokens_senha', $id, null, [
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'validade_horas' => $validadeHoras,
        ]);

        return $token;
    }

    public function buscarValidoPorToken($token)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT * FROM tokens_senha
             WHERE token = :token AND usado_em IS NULL AND expira_em >= NOW()
             LIMIT 1"
        );
        $stmt->execute(['token' => $token]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    /**
     * Usado no reenvio manual de convite (tela de Usuarios): expira qualquer
     * token do mesmo tipo ainda pendente do usuario, pra nao deixar o link
     * antigo (que pode ter ido pro spam) valido ao mesmo tempo que o novo.
     */
    public function invalidarPendentes($usuarioId, $tipo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE tokens_senha SET expira_em = NOW()
             WHERE usuario_id = :usuario_id AND tipo = :tipo AND usado_em IS NULL AND expira_em > NOW()'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'tipo' => $tipo]);
        $linhas = $stmt->rowCount();

        if ($linhas > 0) {
            Auditoria::registrar('invalidar_pendentes', 'tokens_senha', null, null, [
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'quantidade' => $linhas,
            ]);
        }

        return $linhas;
    }

    public function marcarUsado($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE tokens_senha SET usado_em = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('marcar_usado', 'tokens_senha', $id, null, null);
    }
}
