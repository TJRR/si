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
 *
 * Fase 35: uma pergunta pode ter nascido de uma duvida real (duvida_id,
 * migration 114) - ver criar()/listarPorDuvida(). atualizar() de proposito
 * NAO mexe em duvida_id: editar o texto de uma pergunta nunca muda a origem
 * dela.
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

    /**
     * Fase 35: $duvidaId opcional - rastro da duvida que originou a pergunta
     * (migration 114). Default null, e nao parametro obrigatorio, porque a
     * criacao normal (FaqAdminController::novo(), tela "FAQ > Nova pergunta")
     * nao vem de duvida nenhuma e nao precisa saber que isso existe.
     */
    public function criar($pergunta, $resposta, $categoria, $duvidaId = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM perguntas_frequentes');
        $stmt->execute();
        $proximaOrdem = (int) $stmt->fetchColumn();

        $dados = [
            'pergunta' => $pergunta,
            'resposta' => $resposta,
            'categoria' => $categoria,
            'duvida_id' => $duvidaId,
            'ordem' => $proximaOrdem,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO perguntas_frequentes (pergunta, resposta, categoria, duvida_id, ordem)
             VALUES (:pergunta, :resposta, :categoria, :duvida_id, :ordem)'
        );
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'perguntas_frequentes', $id, null, $dados);

        return $id;
    }

    /**
     * Fase 35: perguntas que nasceram de UMA duvida especifica. Alimenta o
     * selo "ja virou FAQ" na tela da duvida e o aviso de promocao repetida.
     * Lista (nao "busca uma"): uma duvida longa pode legitimamente gerar mais
     * de uma pergunta, e a decisao da fase foi AVISAR, nunca impedir.
     */
    public function listarPorDuvida($duvidaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT id, pergunta, categoria FROM perguntas_frequentes WHERE duvida_id = :duvida_id ORDER BY id ASC'
        );
        $stmt->execute(['duvida_id' => $duvidaId]);

        return $stmt->fetchAll();
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
