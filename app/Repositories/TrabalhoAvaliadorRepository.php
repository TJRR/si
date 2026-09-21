<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 49: avaliador avulso de Trabalhos de um evento - pool de pessoas
 * autorizadas a avaliar naquele evento, isolado do perfil global
 * `avaliador` do Concurso de proposito (ver decisao de arquitetura
 * confirmada no plano da fase). Preservacao historica igual
 * EventoAtividadeFacilitadorRepository (Fase 48): remover e' sempre
 * UPDATE removido_em, nunca DELETE fisico.
 */
class TrabalhoAvaliadorRepository
{
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_avaliadores WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    public function buscarPorEventoEUsuario($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM trabalho_avaliadores WHERE evento_id = :evento_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['evento_id' => $eventoId, 'usuario_id' => $usuarioId]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ta.*, u.nome AS usuario_nome, u.email AS usuario_email
             FROM trabalho_avaliadores ta
             INNER JOIN usuarios u ON u.id = ta.usuario_id
             WHERE ta.evento_id = :evento_id AND ta.removido_em IS NULL
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function estaAtivo($eventoId, $usuarioId)
    {
        $registro = $this->buscarPorEventoEUsuario($eventoId, $usuarioId);

        return $registro !== null && $registro['removido_em'] === null;
    }

    /**
     * Fase 49B, achado do teste de fumaça (item 7): usado por
     * EventoAppController::index() para desviar, antes do fallback de
     * "sem inscrição, vá se inscrever", quem ganhou o perfil `inscrito`
     * só por ser avaliador avulso de Trabalhos em algum evento - mesmo
     * padrão já resolvido para o Facilitador (Fase 48) e para o autor de
     * Trabalho (Fase 49). Sem este desvio, o avaliador convidado nunca
     * conseguia chegar em avaliacaoTrabalhos/*, ficava preso na tela de
     * inscrição de um evento do qual ele nunca participou como inscrito.
     */
    public function ehAvaliadorEmQualquerEvento($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM trabalho_avaliadores WHERE usuario_id = :usuario_id AND removido_em IS NULL'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Fase 49B, achado do teste de fumaça (item 6.b): usado por
     * TrabalhoSubmissaoService::submeter() para bloquear a submissão de
     * quem (autor principal ou qualquer coautor, por e-mail) já é
     * avaliador avulso ATIVO daquele evento.
     */
    public function emailJaEhAvaliadorAtivo($eventoId, $email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM trabalho_avaliadores ta
             INNER JOIN usuarios u ON u.id = ta.usuario_id
             WHERE ta.evento_id = :evento_id AND ta.removido_em IS NULL AND u.email = :email'
        );
        $stmt->execute(['evento_id' => $eventoId, 'email' => $email]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Reativa registro removido em vez de duplicar (mesmo criterio de
     * EventoAtividadeFacilitadorRepository::criar()).
     */
    public function criar($eventoId, $usuarioId, $convidadoPor)
    {
        $existente = $this->buscarPorEventoEUsuario($eventoId, $usuarioId);

        if ($existente !== null && $existente['removido_em'] === null) {
            throw new \RuntimeException('Este usuário já está cadastrado como avaliador avulso deste evento.');
        }

        $pdo = Database::conexao();

        if ($existente !== null) {
            $stmt = $pdo->prepare('UPDATE trabalho_avaliadores SET removido_em = NULL, convidado_por = :convidado_por WHERE id = :id');
            $stmt->execute(['convidado_por' => $convidadoPor, 'id' => $existente['id']]);
            $id = (int) $existente['id'];
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO trabalho_avaliadores (evento_id, usuario_id, convidado_por) VALUES (:evento_id, :usuario_id, :convidado_por)'
            );
            $stmt->execute(['evento_id' => $eventoId, 'usuario_id' => $usuarioId, 'convidado_por' => $convidadoPor]);
            $id = (int) $pdo->lastInsertId();
        }

        Auditoria::registrar('criar', 'trabalho_avaliadores', $id, null, ['evento_id' => $eventoId, 'usuario_id' => $usuarioId]);

        return $id;
    }

    public function remover($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_avaliadores WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $antes = $stmt->fetch();

        if ($antes === false || $antes['removido_em'] !== null) {
            return;
        }

        $upd = $pdo->prepare('UPDATE trabalho_avaliadores SET removido_em = NOW() WHERE id = :id');
        $upd->execute(['id' => $id]);

        Auditoria::registrar('remover', 'trabalho_avaliadores', $id, $antes, ['removido_em' => date('Y-m-d H:i:s')]);
    }
}
