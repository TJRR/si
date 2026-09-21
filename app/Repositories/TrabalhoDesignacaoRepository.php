<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: designacao de um avaliador avulso a um trabalho especifico -
 * mesmo estilo de AvaliadorDesignacaoRepository do Concurso. criar()
 * captura PDOException 23000 (UNIQUE trabalho_id+usuario_id) e devolve
 * excecao amigavel, mesmo padrao ja usado em 13 pontos do projeto (ex.:
 * AtividadeAdminController::remover()), achado da revisao desta fase.
 */
class TrabalhoDesignacaoRepository
{
    public function listarPorTrabalho($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.*, u.nome AS usuario_nome
             FROM trabalho_designacoes d
             INNER JOIN usuarios u ON u.id = d.usuario_id
             WHERE d.trabalho_id = :trabalho_id
             ORDER BY d.designado_em ASC'
        );
        $stmt->execute(['trabalho_id' => $trabalhoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_designacoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $designacao = $stmt->fetch();

        return $designacao !== false ? $designacao : null;
    }

    public function buscarPorTrabalhoEUsuario($trabalhoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM trabalho_designacoes WHERE trabalho_id = :trabalho_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['trabalho_id' => $trabalhoId, 'usuario_id' => $usuarioId]);

        $designacao = $stmt->fetch();

        return $designacao !== false ? $designacao : null;
    }

    public function contarPorTrabalho($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM trabalho_designacoes WHERE trabalho_id = :trabalho_id');
        $stmt->execute(['trabalho_id' => $trabalhoId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fase 49B, achado do teste de fumaça (item 6.d): contagem de
     * designações por trabalho de um evento inteiro, numa única consulta
     * agregada (em vez de N chamadas a contarPorTrabalho dentro de um
     * laço), usada para o aviso visual de "abaixo do configurado" em
     * TrabalhoAdminController::recebidos()/recebidoVer(). Trabalho sem
     * nenhuma designação não aparece no resultado (LEFT JOIN não
     * necessário aqui - a ausência na lista já significa zero).
     */
    public function contarPorTrabalhosDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.trabalho_id, COUNT(*) AS total
             FROM trabalho_designacoes d
             INNER JOIN trabalhos t ON t.id = d.trabalho_id
             WHERE t.evento_id = :evento_id
             GROUP BY d.trabalho_id'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        $contagens = [];
        foreach ($stmt->fetchAll() as $linha) {
            $contagens[(int) $linha['trabalho_id']] = (int) $linha['total'];
        }

        return $contagens;
    }

    /**
     * Todos os trabalhos designados a um avaliador, em qualquer evento -
     * usado por TrabalhoAvaliacaoController::index() (visao "meus
     * trabalhos para avaliar", que pode cruzar mais de um evento se a
     * mesma pessoa for avaliadora avulsa em mais de um).
     */
    public function listarTrabalhosDesignadosParaUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.id AS designacao_id, d.trabalho_id, t.evento_id, t.titulo, t.status
             FROM trabalho_designacoes d
             INNER JOIN trabalhos t ON t.id = d.trabalho_id
             WHERE d.usuario_id = :usuario_id
             ORDER BY t.submetido_em ASC'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Trabalhos designados a um avaliador, dentro de um evento -
     * consumido por TrabalhoAvaliacaoController::index(), que dispara
     * TrabalhoRepository::garantirNumerosSigilo() antes de listar quando
     * sigilo_cego estiver ligado (mesmo gatilho lazy do Concurso).
     */
    public function listarTrabalhosDesignadosParaUsuarioNoEvento($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.id AS designacao_id, d.trabalho_id
             FROM trabalho_designacoes d
             INNER JOIN trabalhos t ON t.id = d.trabalho_id
             WHERE t.evento_id = :evento_id AND d.usuario_id = :usuario_id
             ORDER BY t.submetido_em ASC'
        );
        $stmt->execute(['evento_id' => $eventoId, 'usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function criar($trabalhoId, $usuarioId, $designadoPor)
    {
        $pdo = Database::conexao();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO trabalho_designacoes (trabalho_id, usuario_id, designado_por) VALUES (:trabalho_id, :usuario_id, :designado_por)'
            );
            $stmt->execute(['trabalho_id' => $trabalhoId, 'usuario_id' => $usuarioId, 'designado_por' => $designadoPor]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new \RuntimeException('Este avaliador já está designado para este trabalho.');
            }

            throw $e;
        }

        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'trabalho_designacoes', $id, null, ['trabalho_id' => $trabalhoId, 'usuario_id' => $usuarioId]);

        return $id;
    }

    public function remover($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_designacoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $antes = $stmt->fetch();

        $del = $pdo->prepare('DELETE FROM trabalho_designacoes WHERE id = :id');
        $del->execute(['id' => $id]);

        Auditoria::registrar('remover', 'trabalho_designacoes', $id, $antes !== false ? $antes : null, null);
    }
}
