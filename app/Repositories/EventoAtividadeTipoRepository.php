<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 51: catalogo de tipos de atividade por evento (Oficina, Palestra,
 * Sessao solene, Cultural, Experiencia...), usado como etiqueta colorida nos
 * componentes Destaques e Programacao. Catalogo por evento, nunca lista fixa
 * em codigo: cada edicao usa os seus.
 */
class EventoAtividadeTipoRepository
{
    public function listar($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_atividade_tipos WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarDoEvento($eventoId, $id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_atividade_tipos WHERE id = :id AND evento_id = :evento_id LIMIT 1');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM evento_atividade_tipos WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO evento_atividade_tipos (evento_id, nome, cor, ordem)
             VALUES (:evento_id, :nome, :cor, :ordem)'
        );
        $stmt->execute([
            'evento_id' => $eventoId,
            'nome' => $dados['nome'],
            'cor' => $dados['cor'],
            'ordem' => $proximaOrdem,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_atividade_tipos', $id, null, $dados + ['evento_id' => $eventoId]);

        return $id;
    }

    public function atualizar($eventoId, $id, array $dados)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE evento_atividade_tipos SET nome = :nome, cor = :cor WHERE id = :id AND evento_id = :evento_id');
        $stmt->execute([
            'nome' => $dados['nome'],
            'cor' => $dados['cor'],
            'id' => $id,
            'evento_id' => $eventoId,
        ]);

        Auditoria::registrar('atualizar', 'evento_atividade_tipos', $id, $antes, $dados);
    }

    /**
     * Atividade que ja usa o tipo impede a remocao: a chave estrangeira em
     * evento_atividades.tipo_id nao tem ON DELETE, entao a checagem acontece
     * aqui, com mensagem que o Admin entende, em vez de erro cru do banco.
     */
    public function emUso($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_atividades WHERE tipo_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function remover($eventoId, $id)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_atividade_tipos WHERE id = :id AND evento_id = :evento_id');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('remover', 'evento_atividade_tipos', $id, $antes, null);
    }

    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_atividade_tipos SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_atividade_tipos', null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
