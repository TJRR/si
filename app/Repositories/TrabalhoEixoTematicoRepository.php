<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: catalogo de eixos tematicos de Trabalhos, cadastrado pelo
 * Admin, por evento - nunca semeado via migration (mesmo espirito de
 * EventoPerfilOrganizacaoRepository, Fase 48). Se um evento nao tiver
 * nenhuma linha aqui, o campo de eixo tematico nao aparece no formulario
 * de submissao (ver TrabalhoSubmissaoService).
 */
class TrabalhoEixoTematicoRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_eixos_tematicos WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_eixos_tematicos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $eixo = $stmt->fetch();

        return $eixo !== false ? $eixo : null;
    }

    public function criar($eventoId, $nome, $descricao, $ordem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalho_eixos_tematicos (evento_id, nome, descricao, ordem) VALUES (:evento_id, :nome, :descricao, :ordem)'
        );
        $stmt->execute(['evento_id' => $eventoId, 'nome' => $nome, 'descricao' => $descricao, 'ordem' => $ordem]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trabalho_eixos_tematicos', $id, null, ['evento_id' => $eventoId, 'nome' => $nome]);

        return $id;
    }

    public function atualizar($id, $nome, $descricao, $ordem)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE trabalho_eixos_tematicos SET nome = :nome, descricao = :descricao, ordem = :ordem WHERE id = :id'
        );
        $stmt->execute(['nome' => $nome, 'descricao' => $descricao, 'ordem' => $ordem, 'id' => $id]);

        Auditoria::registrar('atualizar', 'trabalho_eixos_tematicos', $id, $antes, ['nome' => $nome, 'descricao' => $descricao, 'ordem' => $ordem]);
    }

    /**
     * Sem soft-delete: se ja houver trabalho vinculado, a FK recusa o
     * DELETE (o Controller trata a excecao com mensagem clara), mesmo
     * criterio ja usado em EventoPerfilOrganizacaoRepository::remover().
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM trabalho_eixos_tematicos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'trabalho_eixos_tematicos', $id, $antes, null);
    }
}
