<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 18 (3.10/4.4) - banco GLOBAL e acumulativo de perguntas frequentes,
 * independente de concurso. A ativacao por edicao fica em
 * FaqConcursoRepository (tabela faq_concurso).
 */
class PerguntaFrequenteRepository
{
    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM perguntas_frequentes ORDER BY categoria ASC, ordem ASC, id ASC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM perguntas_frequentes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $faq = $stmt->fetch();

        return $faq !== false ? $faq : null;
    }

    public function criar($pergunta, $resposta, $categoria)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM perguntas_frequentes');
        $stmt->execute();
        $proximaOrdem = (int) $stmt->fetchColumn();

        $dados = ['pergunta' => $pergunta, 'resposta' => $resposta, 'categoria' => $categoria, 'ordem' => $proximaOrdem];

        $stmt = $pdo->prepare(
            'INSERT INTO perguntas_frequentes (pergunta, resposta, categoria, ordem) VALUES (:pergunta, :resposta, :categoria, :ordem)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'perguntas_frequentes', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $pergunta, $resposta, $categoria)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $dados = ['pergunta' => $pergunta, 'resposta' => $resposta, 'categoria' => $categoria];
        $stmt = $pdo->prepare('UPDATE perguntas_frequentes SET pergunta = :pergunta, resposta = :resposta, categoria = :categoria WHERE id = :id');
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', 'perguntas_frequentes', $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM perguntas_frequentes WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'perguntas_frequentes', $id, $antes, null);
    }
}
