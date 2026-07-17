<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 18 (3.12) - mensagens recebidas pelo formulario de contato nativo,
 * quando ativado em contatos_concurso.formulario_contato_ativo.
 */
class MensagemContatoRepository
{
    public function criar($concursoId, $nome, $email, $mensagem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO mensagens_contato (concurso_id, nome, email, mensagem) VALUES (:concurso_id, :nome, :email, :mensagem)'
        );
        $stmt->execute([
            'concurso_id' => $concursoId,
            'nome' => $nome,
            'email' => $email,
            'mensagem' => $mensagem,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function listarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM mensagens_contato WHERE concurso_id = :concurso_id ORDER BY criado_em DESC');
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }
}
