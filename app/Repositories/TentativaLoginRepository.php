<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 31 (Auditoria de Seguranca, achado #11): rate limiting de login por
 * e-mail digitado - conta falhas recentes independente de o e-mail existir
 * ou nao no sistema (nao abre canal novo de enumeracao).
 */
class TentativaLoginRepository
{
    public function registrarFalha($email, $ip)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO tentativas_login_falhas (email, ip_origem) VALUES (:email, :ip)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
    }

    public function contarFalhasRecentes($email, $minutos = 15)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM tentativas_login_falhas
             WHERE email = :email AND criado_em >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)'
        );
        $stmt->bindValue('email', $email);
        $stmt->bindValue('minutos', $minutos, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function limparFalhas($email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM tentativas_login_falhas WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }
}
