<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 18 (3.12) - mensagens recebidas pelo formulario de contato nativo,
 * quando ativado em contatos_concurso.formulario_contato_ativo. Fase 19
 * (#84 v2): contato deixou de ser escopado por concurso - mensagens
 * tambem, viram uma lista global cronologica.
 */
class MensagemContatoRepository
{
    public function criar($nome, $email, $mensagem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO mensagens_contato (nome, email, mensagem) VALUES (:nome, :email, :mensagem)'
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'mensagem' => $mensagem,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM mensagens_contato ORDER BY criado_em DESC')->fetchAll();
    }
}
