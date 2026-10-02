<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Limite de tentativas da pagina publica de conferencia de certificado
 * (migration 200). Ver Implantar.md, secao 13.18.
 */
class CertificadoConferenciaFalhaRepository
{
    const LIMITE_TENTATIVAS = 5;

    const JANELA_MINUTOS = 15;

    public function registrarFalha($ip)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare('INSERT INTO evento_certificado_conferencia_falhas (ip_origem) VALUES (:ip)');
            $stmt->execute(['ip' => $ip]);
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao registrar tentativa de conferencia: ' . $e->getMessage());
        }
    }

    public function contarFalhasRecentes($ip, $minutos = self::JANELA_MINUTOS)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM evento_certificado_conferencia_falhas
                  WHERE ip_origem = :ip AND criado_em >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)'
            );
            $stmt->bindValue('ip', $ip);
            $stmt->bindValue('minutos', (int) $minutos, \PDO::PARAM_INT);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao contar tentativas de conferencia: ' . $e->getMessage());

            return 0;
        }
    }

    public function excedeuOLimite($ip)
    {
        return $this->contarFalhasRecentes($ip) >= self::LIMITE_TENTATIVAS;
    }

    /**
     * Apaga as falhas do endereco que acabou de acertar um codigo.
     */
    public function limparFalhas($ip)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare('DELETE FROM evento_certificado_conferencia_falhas WHERE ip_origem = :ip');
            $stmt->execute(['ip' => $ip]);
        } catch (\PDOException $e) {
            error_log('[Certificado] Falha ao limpar tentativas de conferencia: ' . $e->getMessage());
        }
    }
}
