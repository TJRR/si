<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class NotificacaoRepository
{
    public function criar($evento, $templateCodigo, $destinatarioEmail, $assunto, $corpo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO notificacoes (evento, canal, template_codigo, destinatario_email, assunto, corpo, status)
             VALUES (:evento, \'email\', :template_codigo, :destinatario_email, :assunto, :corpo, \'pendente\')'
        );
        $stmt->execute([
            'evento' => $evento,
            'template_codigo' => $templateCodigo,
            'destinatario_email' => $destinatarioEmail,
            'assunto' => $assunto,
            'corpo' => $corpo,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function marcarEnviada($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE notificacoes SET status = 'enviado', enviado_em = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public function marcarFalhou($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare("UPDATE notificacoes SET status = 'falhou' WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
