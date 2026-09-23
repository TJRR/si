<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 51: base comum aos componentes da pagina publica do Evento que seguem
 * o formato "uma instancia com varios itens dentro" (Contagem regressiva,
 * Cronograma, Cartoes, Destaques, Programacao, Perguntas frequentes). Local
 * e o unico componente sem itens e por isso so usa a metade de cima daqui.
 *
 * Nenhuma classe do Concurso herda desta nem e alterada por ela: mesmo
 * isolamento adotado na Fase 50 para EventoConteudoRepositorioBase, que trata
 * do outro formato (lista unica por evento: Quadros e Faixas).
 *
 * Toda operacao de item carrega o evento junto (JOIN com a tabela-mae e
 * evento_id no WHERE), nunca so o id do item: um envio manipulado com id de
 * item de outro evento nao afeta nenhuma linha. Mesmo cuidado aplicado na
 * Fase 50 aos metodos de reordenar.
 */
abstract class EventoSecaoRepositorioBase
{
    abstract protected function tabela();

    abstract protected function colunas();

    protected function tabelaItens()
    {
        return null;
    }

    protected function colunasItens()
    {
        return [];
    }

    public function listar($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM ' . $this->tabela() . ' WHERE evento_id = :evento_id ORDER BY id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM ' . $this->tabela() . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function buscarDoEvento($eventoId, $id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM ' . $this->tabela() . ' WHERE id = :id AND evento_id = :evento_id LIMIT 1');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $colunas = array_merge(['evento_id'], $this->colunas());

        $placeholders = implode(', ', array_map(function ($coluna) {
            return ':' . $coluna;
        }, $colunas));

        $stmt = $pdo->prepare('INSERT INTO ' . $this->tabela() . ' (' . implode(', ', $colunas) . ') VALUES (' . $placeholders . ')');
        $stmt->execute($dados + ['evento_id' => $eventoId]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', $this->tabela(), $id, null, $dados + ['evento_id' => $eventoId]);

        return $id;
    }

    public function atualizar($eventoId, $id, array $dados)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();

        $atribuicoes = implode(', ', array_map(function ($coluna) {
            return $coluna . ' = :' . $coluna;
        }, $this->colunas()));

        $stmt = $pdo->prepare('UPDATE ' . $this->tabela() . ' SET ' . $atribuicoes . ' WHERE id = :id AND evento_id = :evento_id');
        $stmt->execute($dados + ['id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('atualizar', $this->tabela(), $id, $antes, $dados);
    }

    /**
     * Remove a instancia e, junto, a linha de ordem da pagina que apontava
     * para ela (a referencia e polimorfica, nao ha chave estrangeira que faca
     * isso sozinha). Os itens caem pela chave estrangeira da tabela filha,
     * que e ON DELETE CASCADE.
     */
    public function remover($eventoId, $id, $tipoSecao)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            (new EventoSecaoOrdemRepository())->removerSecao($eventoId, $tipoSecao, $id);

            $stmt = $pdo->prepare('DELETE FROM ' . $this->tabela() . ' WHERE id = :id AND evento_id = :evento_id');
            $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('remover', $this->tabela(), $id, $antes, null);
    }

    public function listarItens($secaoId)
    {
        if ($this->tabelaItens() === null) {
            return [];
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM ' . $this->tabelaItens() . ' WHERE secao_id = :secao_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['secao_id' => $secaoId]);

        return $stmt->fetchAll();
    }

    public function buscarItem($eventoId, $itemId)
    {
        if ($this->tabelaItens() === null) {
            return null;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.* FROM ' . $this->tabelaItens() . ' i
             INNER JOIN ' . $this->tabela() . ' s ON s.id = i.secao_id
             WHERE i.id = :id AND s.evento_id = :evento_id LIMIT 1'
        );
        $stmt->execute(['id' => $itemId, 'evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function criarItem($eventoId, $secaoId, array $dados)
    {
        if ($this->buscarDoEvento($eventoId, $secaoId) === null) {
            return null;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM ' . $this->tabelaItens() . ' WHERE secao_id = :secao_id');
        $stmt->execute(['secao_id' => $secaoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $colunas = array_merge(['secao_id', 'ordem'], $this->colunasItens());

        $placeholders = implode(', ', array_map(function ($coluna) {
            return ':' . $coluna;
        }, $colunas));

        $stmt = $pdo->prepare('INSERT INTO ' . $this->tabelaItens() . ' (' . implode(', ', $colunas) . ') VALUES (' . $placeholders . ')');
        $stmt->execute($dados + ['secao_id' => $secaoId, 'ordem' => $proximaOrdem]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', $this->tabelaItens(), $id, null, $dados + ['secao_id' => $secaoId]);

        return $id;
    }

    public function atualizarItem($eventoId, $itemId, array $dados)
    {
        $antes = $this->buscarItem($eventoId, $itemId);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();

        $atribuicoes = implode(', ', array_map(function ($coluna) {
            return 'i.' . $coluna . ' = :' . $coluna;
        }, $this->colunasItens()));

        $stmt = $pdo->prepare(
            'UPDATE ' . $this->tabelaItens() . ' i
             INNER JOIN ' . $this->tabela() . ' s ON s.id = i.secao_id
             SET ' . $atribuicoes . '
             WHERE i.id = :id AND s.evento_id = :evento_id'
        );
        $stmt->execute($dados + ['id' => $itemId, 'evento_id' => $eventoId]);

        Auditoria::registrar('atualizar', $this->tabelaItens(), $itemId, $antes, $dados);
    }

    public function removerItem($eventoId, $itemId)
    {
        $antes = $this->buscarItem($eventoId, $itemId);

        if ($antes === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'DELETE i FROM ' . $this->tabelaItens() . ' i
             INNER JOIN ' . $this->tabela() . ' s ON s.id = i.secao_id
             WHERE i.id = :id AND s.evento_id = :evento_id'
        );
        $stmt->execute(['id' => $itemId, 'evento_id' => $eventoId]);

        Auditoria::registrar('remover', $this->tabelaItens(), $itemId, $antes, null);
    }

    public function reordenarItens($eventoId, $secaoId, array $ids)
    {
        if ($this->buscarDoEvento($eventoId, $secaoId) === null) {
            return;
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE ' . $this->tabelaItens() . ' SET ordem = :ordem WHERE id = :id AND secao_id = :secao_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'secao_id' => $secaoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', $this->tabelaItens(), null, null, ['secao_id' => $secaoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
