<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 48 (correcao pos-teste de fumaca): perfis da equipe de organizacao
 * do evento (Instrutor, Professor, Palestrante, ...) - cadastrados pelo
 * Admin, por evento, nunca fixos no codigo. Usado por
 * EventoAtividadeFacilitadorRepository (perfil_id) ao vincular um
 * facilitador a uma atividade.
 */
class EventoPerfilOrganizacaoRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_perfis_organizacao WHERE evento_id = :evento_id ORDER BY nome ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_perfis_organizacao WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $perfil = $stmt->fetch();

        return $perfil !== false ? $perfil : null;
    }

    public function criar($eventoId, $nome)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('INSERT INTO evento_perfis_organizacao (evento_id, nome) VALUES (:evento_id, :nome)');
        $stmt->execute(['evento_id' => $eventoId, 'nome' => $nome]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_perfis_organizacao', $id, null, ['evento_id' => $eventoId, 'nome' => $nome]);

        return $id;
    }

    /**
     * Sem soft-delete: se ja houver facilitador vinculado a este perfil, a
     * FK recusa o DELETE (o Controller trata a exceção com mensagem clara),
     * mesmo criterio ja usado em EventoAtividadeRepository::remover().
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_perfis_organizacao WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'evento_perfis_organizacao', $id, $antes, null);
    }
}
