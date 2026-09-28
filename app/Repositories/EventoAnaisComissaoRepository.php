<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 54: comissoes que aparecem nas paginas iniciais do volume dos Anais
 * (evento_anais_comissoes) e os membros de cada uma
 * (evento_anais_comissao_membros). Toda busca e toda gravacao por id
 * conferem o evento: um id de outro evento forjado na requisicao nao
 * encontra nada e nao grava nada.
 */
class EventoAnaisComissaoRepository
{
    /**
     * Comissoes do evento na ordem do volume, cada uma com a chave
     * 'membros' (tambem ordenados).
     */
    public function listarComMembros($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais_comissoes WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        $comissoes = [];

        foreach ($stmt->fetchAll() as $comissao) {
            $comissao['membros'] = [];
            $comissoes[(int) $comissao['id']] = $comissao;
        }

        if (empty($comissoes)) {
            return [];
        }

        $stmt = $pdo->prepare(
            'SELECT m.*
             FROM evento_anais_comissao_membros m
             INNER JOIN evento_anais_comissoes c ON c.id = m.comissao_id
             WHERE c.evento_id = :evento_id
             ORDER BY m.ordem ASC, m.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        foreach ($stmt->fetchAll() as $membro) {
            $comissaoId = (int) $membro['comissao_id'];

            if (isset($comissoes[$comissaoId])) {
                $comissoes[$comissaoId]['membros'][] = $membro;
            }
        }

        return array_values($comissoes);
    }

    public function buscarDoEvento($eventoId, $id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais_comissoes WHERE id = :id AND evento_id = :evento_id LIMIT 1');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        $comissao = $stmt->fetch();

        return $comissao !== false ? $comissao : null;
    }

    /**
     * A comissao nova entra no fim da lista.
     */
    public function criar($eventoId, $nome)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM evento_anais_comissoes WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);
        $ordem = (int) $stmt->fetchColumn();

        $pdo->prepare('INSERT INTO evento_anais_comissoes (evento_id, nome, ordem) VALUES (:evento_id, :nome, :ordem)')
            ->execute(['evento_id' => $eventoId, 'nome' => $nome, 'ordem' => $ordem]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_anais_comissoes', $id, null, ['evento_id' => (int) $eventoId, 'nome' => $nome]);

        return $id;
    }

    /**
     * Devolve false se a comissao nao for deste evento.
     */
    public function atualizar($eventoId, $id, $nome)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return false;
        }

        $pdo = Database::conexao();
        $pdo->prepare('UPDATE evento_anais_comissoes SET nome = :nome WHERE id = :id AND evento_id = :evento_id')
            ->execute(['nome' => $nome, 'id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('atualizar', 'evento_anais_comissoes', (int) $id, ['nome' => $antes['nome']], ['nome' => $nome]);

        return true;
    }

    /**
     * Os membros saem junto (ON DELETE CASCADE). Devolve false se a
     * comissao nao for deste evento.
     */
    public function remover($eventoId, $id)
    {
        $antes = $this->buscarDoEvento($eventoId, $id);

        if ($antes === null) {
            return false;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_anais_comissoes WHERE id = :id AND evento_id = :evento_id');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('remover', 'evento_anais_comissoes', (int) $id, $antes, null);

        return $stmt->rowCount() > 0;
    }

    /**
     * Grava a ordem das comissoes na ordem da lista recebida; id de outro
     * evento nao casa com o WHERE e e' ignorado.
     */
    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_anais_comissoes SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach (array_values($ids) as $indice => $id) {
                $stmt->execute(['ordem' => $indice + 1, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('reordenar', 'evento_anais_comissoes', null, null, ['evento_id' => (int) $eventoId, 'ids' => array_values($ids)]);
    }

    /**
     * Membro com a comissao dele, so' se a comissao for deste evento.
     */
    public function buscarMembroDoEvento($eventoId, $id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT m.*, c.nome AS comissao_nome
             FROM evento_anais_comissao_membros m
             INNER JOIN evento_anais_comissoes c ON c.id = m.comissao_id
             WHERE m.id = :id AND c.evento_id = :evento_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        $membro = $stmt->fetch();

        return $membro !== false ? $membro : null;
    }

    /**
     * $dados: nome, funcao, instituicao (ja conferidos). O membro novo entra
     * no fim da comissao. Devolve o id, ou null se a comissao nao for deste
     * evento.
     */
    public function criarMembro($eventoId, $comissaoId, array $dados)
    {
        if ($this->buscarDoEvento($eventoId, $comissaoId) === null) {
            return null;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM evento_anais_comissao_membros WHERE comissao_id = :comissao_id');
        $stmt->execute(['comissao_id' => $comissaoId]);
        $ordem = (int) $stmt->fetchColumn();

        $registro = [
            'comissao_id' => $comissaoId,
            'nome' => $dados['nome'],
            'funcao' => $dados['funcao'],
            'instituicao' => $dados['instituicao'],
            'ordem' => $ordem,
        ];

        $pdo->prepare(
            'INSERT INTO evento_anais_comissao_membros (comissao_id, nome, funcao, instituicao, ordem)
             VALUES (:comissao_id, :nome, :funcao, :instituicao, :ordem)'
        )->execute($registro);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_anais_comissao_membros', $id, null, $registro);

        return $id;
    }

    /**
     * Devolve false se o membro nao for de uma comissao deste evento.
     */
    public function atualizarMembro($eventoId, $id, array $dados)
    {
        $antes = $this->buscarMembroDoEvento($eventoId, $id);

        if ($antes === null) {
            return false;
        }

        $valores = [
            'nome' => $dados['nome'],
            'funcao' => $dados['funcao'],
            'instituicao' => $dados['instituicao'],
        ];

        $pdo = Database::conexao();
        $pdo->prepare(
            'UPDATE evento_anais_comissao_membros m
             INNER JOIN evento_anais_comissoes c ON c.id = m.comissao_id
             SET m.nome = :nome, m.funcao = :funcao, m.instituicao = :instituicao
             WHERE m.id = :id AND c.evento_id = :evento_id'
        )->execute($valores + ['id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('atualizar', 'evento_anais_comissao_membros', (int) $id, [
            'nome' => $antes['nome'],
            'funcao' => $antes['funcao'],
            'instituicao' => $antes['instituicao'],
        ], $valores);

        return true;
    }

    public function removerMembro($eventoId, $id)
    {
        $antes = $this->buscarMembroDoEvento($eventoId, $id);

        if ($antes === null) {
            return false;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'DELETE m FROM evento_anais_comissao_membros m
             INNER JOIN evento_anais_comissoes c ON c.id = m.comissao_id
             WHERE m.id = :id AND c.evento_id = :evento_id'
        );
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('remover', 'evento_anais_comissao_membros', (int) $id, $antes, null);

        return $stmt->rowCount() > 0;
    }

    /**
     * Grava a ordem dos membros de uma comissao. Devolve false (sem gravar
     * nada) se a comissao nao for deste evento; membro de outra comissao
     * nao casa com o WHERE e e' ignorado.
     */
    public function reordenarMembros($eventoId, $comissaoId, array $ids)
    {
        if ($this->buscarDoEvento($eventoId, $comissaoId) === null) {
            return false;
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_anais_comissao_membros SET ordem = :ordem WHERE id = :id AND comissao_id = :comissao_id');

            foreach (array_values($ids) as $indice => $id) {
                $stmt->execute(['ordem' => $indice + 1, 'id' => (int) $id, 'comissao_id' => $comissaoId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('reordenar', 'evento_anais_comissao_membros', null, null, [
            'evento_id' => (int) $eventoId,
            'comissao_id' => (int) $comissaoId,
            'ids' => array_values($ids),
        ]);

        return true;
    }
}
