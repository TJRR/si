<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 50: base comum aos repositorios de conteudo proprio de cada Evento
 * (Slides/Faixas/Blocos de conteudo) - nenhuma classe do Concurso herda
 * daqui nem e alterada por esta classe (isolamento total, ver plano da
 * fase). Cada subclasse so declara a tabela e as colunas graváveis; toda
 * leitura/escrita e sempre filtrada por evento_id, inclusive em
 * criar()/reordenar(), para um POST manipulado nao conseguir tocar
 * registro de outro evento (mesmo cuidado ja usado hoje em
 * EventoCampoInscricaoRepository::reordenar()).
 */
abstract class EventoConteudoRepositorioBase
{
    abstract protected function tabela();

    abstract protected function colunas();

    public function listar($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM ' . $this->tabela() . ' WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function listarAtivos($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM ' . $this->tabela() . ' WHERE evento_id = :evento_id AND ativo = 1 ORDER BY ordem ASC, id ASC');
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

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM ' . $this->tabela() . ' WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $campos = $dados + ['evento_id' => $eventoId, 'ordem' => $proximaOrdem];
        $colunas = array_merge(['evento_id', 'ordem'], $this->colunas());

        $placeholders = implode(', ', array_map(function ($coluna) {
            return ':' . $coluna;
        }, $colunas));

        $stmt = $pdo->prepare(
            'INSERT INTO ' . $this->tabela() . ' (' . implode(', ', $colunas) . ') VALUES (' . $placeholders . ')'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', $this->tabela(), $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();

        $atribuicoes = implode(', ', array_map(function ($coluna) {
            return $coluna . ' = :' . $coluna;
        }, $this->colunas()));

        $stmt = $pdo->prepare('UPDATE ' . $this->tabela() . ' SET ' . $atribuicoes . ' WHERE id = :id');
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar', $this->tabela(), $id, $antes, $dados);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM ' . $this->tabela() . ' WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', $this->tabela(), $id, $antes, null);
    }

    /**
     * Reordenacao em lote (arrastar-e-soltar) - so grava a nova posicao de
     * ids que realmente pertencem a este evento (WHERE evento_id tambem na
     * clausula, nao so id): um POST manipulado com id de outro evento
     * simplesmente nao afeta nenhuma linha.
     */
    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE ' . $this->tabela() . ' SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', $this->tabela(), null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
