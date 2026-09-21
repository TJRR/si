<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 39 (revisada): leitura/escrita da tabela `eventos` - entidade de
 * primeiro nivel, mesmo status estrutural de Concurso (multiplos registros
 * possiveis, nunca um registro unico/singleton). Nome do repository evita a
 * palavra "Evento" sozinha porque ja' existe EventoCronogramaRepository
 * (linha do tempo publica), um conceito diferente.
 */
class SemanaInovacaoRepository
{
    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM eventos ORDER BY data_inicio DESC, id DESC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM eventos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $evento = $stmt->fetch();

        return $evento !== false ? $evento : null;
    }

    /**
     * O evento ativo mais recente - usado pela tela publica de inscricao
     * (ainda nao ha' selecao explicita de qual evento na URL publica).
     */
    public function buscarAtivoMaisRecente()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare("SELECT * FROM eventos WHERE status = 'ativo' ORDER BY data_inicio DESC, id DESC LIMIT 1");
        $stmt->execute();

        $evento = $stmt->fetch();

        return $evento !== false ? $evento : null;
    }

    public function criar(array $dados)
    {
        $pdo = Database::conexao();
        $campos = [
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'],
            'mensagem_confirmacao_inscricao' => $dados['mensagem_confirmacao_inscricao'],
            'data_inicio' => $dados['data_inicio'],
            'data_fim' => $dados['data_fim'],
            'status' => $dados['status'],
            'modo_credenciamento' => $dados['modo_credenciamento'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO eventos (nome, descricao, mensagem_confirmacao_inscricao, data_inicio, data_fim, status, modo_credenciamento)
             VALUES (:nome, :descricao, :mensagem_confirmacao_inscricao, :data_inicio, :data_fim, :status, :modo_credenciamento)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'eventos', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = [
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'],
            'mensagem_confirmacao_inscricao' => $dados['mensagem_confirmacao_inscricao'],
            'data_inicio' => $dados['data_inicio'],
            'data_fim' => $dados['data_fim'],
            'status' => $dados['status'],
            'modo_credenciamento' => $dados['modo_credenciamento'],
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE eventos
                SET nome = :nome, descricao = :descricao, mensagem_confirmacao_inscricao = :mensagem_confirmacao_inscricao,
                    data_inicio = :data_inicio, data_fim = :data_fim, status = :status, modo_credenciamento = :modo_credenciamento
              WHERE id = :id'
        );
        $stmt->execute($campos + ['id' => $id]);

        Auditoria::registrar('atualizar', 'eventos', $id, $antes, $campos);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM eventos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'eventos', $id, $antes, null);
    }
}
