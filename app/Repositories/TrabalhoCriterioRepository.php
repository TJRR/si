<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: criterios de avaliacao de Trabalhos, cadastrados pelo Admin,
 * por evento - inspirado no estilo de CriterioAvaliacaoRepository do
 * Concurso, mas em tabela propria, sem FK para etapas (decisao de
 * arquitetura numero 5 do plano mestre). nota_maxima funciona como peso
 * do criterio na soma.
 */
class TrabalhoCriterioRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_criterios WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_criterios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $criterio = $stmt->fetch();

        return $criterio !== false ? $criterio : null;
    }

    public function notaMaximaTotal($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(nota_maxima), 0) FROM trabalho_criterios WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);

        return (float) $stmt->fetchColumn();
    }

    public function criar($eventoId, $nome, $descricao, $notaMaxima, $ordem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalho_criterios (evento_id, nome, descricao, nota_maxima, ordem)
             VALUES (:evento_id, :nome, :descricao, :nota_maxima, :ordem)'
        );
        $stmt->execute([
            'evento_id' => $eventoId, 'nome' => $nome, 'descricao' => $descricao,
            'nota_maxima' => $notaMaxima, 'ordem' => $ordem,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trabalho_criterios', $id, null, ['evento_id' => $eventoId, 'nome' => $nome, 'nota_maxima' => $notaMaxima]);

        return $id;
    }

    public function atualizar($id, $nome, $descricao, $notaMaxima, $ordem)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE trabalho_criterios SET nome = :nome, descricao = :descricao, nota_maxima = :nota_maxima, ordem = :ordem WHERE id = :id'
        );
        $stmt->execute(['nome' => $nome, 'descricao' => $descricao, 'nota_maxima' => $notaMaxima, 'ordem' => $ordem, 'id' => $id]);

        Auditoria::registrar('atualizar', 'trabalho_criterios', $id, $antes, ['nome' => $nome, 'nota_maxima' => $notaMaxima, 'ordem' => $ordem]);
    }

    /**
     * Troca a ordem com o vizinho (swap), mesmo padrao de
     * CriterioAvaliacaoRepository::mover() do Concurso.
     */
    public function mover($id, $direcao)
    {
        $atual = $this->buscarPorId($id);

        if ($atual === null) {
            return;
        }

        $pdo = Database::conexao();
        $operador = $direcao === 'cima' ? '<' : '>';
        $ordemDirecao = $direcao === 'cima' ? 'DESC' : 'ASC';

        $stmt = $pdo->prepare(
            "SELECT * FROM trabalho_criterios
             WHERE evento_id = :evento_id AND ordem {$operador} :ordem
             ORDER BY ordem {$ordemDirecao} LIMIT 1"
        );
        $stmt->execute(['evento_id' => $atual['evento_id'], 'ordem' => $atual['ordem']]);
        $vizinho = $stmt->fetch();

        if ($vizinho === false) {
            return;
        }

        $pdo->beginTransaction();
        $upd = $pdo->prepare('UPDATE trabalho_criterios SET ordem = :ordem WHERE id = :id');
        $upd->execute(['ordem' => $vizinho['ordem'], 'id' => $atual['id']]);
        $upd->execute(['ordem' => $atual['ordem'], 'id' => $vizinho['id']]);
        $pdo->commit();

        Auditoria::registrar('mover', 'trabalho_criterios', $id, $atual, ['ordem' => $vizinho['ordem']]);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM trabalho_criterios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'trabalho_criterios', $id, $antes, null);
    }
}
