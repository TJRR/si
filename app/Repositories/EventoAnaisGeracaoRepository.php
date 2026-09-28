<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 54: fila dos pedidos de geracao do volume dos Anais
 * (evento_anais_geracoes). O servidor web so' registra o pedido
 * (solicitar); quem gera e' a rotina database/gerar_anais.php, que reserva
 * o pedido mais antigo e marca concluida ou falhou no fim.
 */
class EventoAnaisGeracaoRepository
{
    /**
     * Registra um pedido e devolve o id, ou null se o evento ja tiver um
     * pedido na fila ou em geracao. A linha de evento_anais_montagem do
     * evento e' travada com FOR UPDATE: dois cliques simultaneos se
     * serializam e o segundo enxerga o pedido do primeiro.
     */
    public function solicitar($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $pdo->prepare(
            'INSERT INTO evento_anais_montagem (evento_id) VALUES (:evento_id)
             ON DUPLICATE KEY UPDATE evento_id = evento_id'
        )->execute(['evento_id' => $eventoId]);

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM evento_anais_montagem WHERE evento_id = :evento_id LIMIT 1 FOR UPDATE');
            $stmt->execute(['evento_id' => $eventoId]);
            $stmt->fetch();

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM evento_anais_geracoes
                 WHERE evento_id = :evento_id AND situacao IN ('pendente', 'processando')"
            );
            $stmt->execute(['evento_id' => $eventoId]);

            if ((int) $stmt->fetchColumn() > 0) {
                $pdo->rollBack();

                return null;
            }

            $pdo->prepare('INSERT INTO evento_anais_geracoes (evento_id, solicitado_por) VALUES (:evento_id, :solicitado_por)')
                ->execute(['evento_id' => $eventoId, 'solicitado_por' => $usuarioId]);
            $id = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('solicitar_geracao', 'evento_anais_geracoes', $id, null, [
            'evento_id' => (int) $eventoId,
            'solicitado_por' => $usuarioId,
        ]);

        return $id;
    }

    public function existeEmAndamento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM evento_anais_geracoes
             WHERE evento_id = :evento_id AND situacao IN ('pendente', 'processando')"
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Ultimos pedidos do evento, mais recentes primeiro, com o nome de quem
     * pediu e o numero da versao criada (nulo se a versao foi removida
     * depois na aba Anais).
     */
    public function listarPorEvento($eventoId, $limite = 10)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT g.*, u.nome AS solicitado_por_nome, v.numero AS versao_numero
             FROM evento_anais_geracoes g
             LEFT JOIN usuarios u ON u.id = g.solicitado_por
             LEFT JOIN evento_anais_versoes v ON v.id = g.versao_id
             WHERE g.evento_id = :evento_id
             ORDER BY g.id DESC
             LIMIT ' . (int) $limite
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Pega o pedido pendente mais antigo e o marca como em geracao. O UPDATE
     * condicionado a situacao 'pendente' garante que dois processos nunca
     * gerem o mesmo pedido: quem nao conseguir marcar tenta o seguinte.
     */
    public function reservarProximo()
    {
        $pdo = Database::conexao();

        for ($tentativa = 1; $tentativa <= 3; $tentativa++) {
            $stmt = $pdo->query(
                "SELECT * FROM evento_anais_geracoes
                 WHERE situacao = 'pendente'
                 ORDER BY solicitado_em ASC, id ASC
                 LIMIT 1"
            );
            $pedido = $stmt->fetch();

            if ($pedido === false) {
                return null;
            }

            $marcacao = $pdo->prepare(
                "UPDATE evento_anais_geracoes SET situacao = 'processando', iniciado_em = NOW()
                 WHERE id = :id AND situacao = 'pendente'"
            );
            $marcacao->execute(['id' => $pedido['id']]);

            if ($marcacao->rowCount() === 1) {
                $pedido['situacao'] = 'processando';

                return $pedido;
            }
        }

        return null;
    }

    /**
     * Chamado pela rotina ja com a trava de execucao unica: nesse momento
     * nenhum outro processo esta gerando, entao um pedido ainda "em geracao"
     * sobrou de uma execucao que morreu no meio (memoria, tempo, reinicio) e
     * travaria o evento para sempre. Devolve quantos foram marcados.
     */
    public function marcarInterrompidos($mensagem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "UPDATE evento_anais_geracoes SET situacao = 'falhou', mensagem = :mensagem, concluido_em = NOW()
             WHERE situacao = 'processando'"
        );
        $stmt->execute(['mensagem' => $mensagem]);

        return $stmt->rowCount();
    }

    public function concluir($id, $versaoId, $mensagem)
    {
        $pdo = Database::conexao();
        $pdo->prepare(
            "UPDATE evento_anais_geracoes
             SET situacao = 'concluida', versao_id = :versao_id, mensagem = :mensagem, concluido_em = NOW()
             WHERE id = :id"
        )->execute(['versao_id' => $versaoId, 'mensagem' => $mensagem, 'id' => $id]);

        Auditoria::registrar('concluir_geracao', 'evento_anais_geracoes', (int) $id, null, ['versao_id' => (int) $versaoId]);
    }

    public function falhar($id, $mensagem)
    {
        $pdo = Database::conexao();
        $pdo->prepare(
            "UPDATE evento_anais_geracoes SET situacao = 'falhou', mensagem = :mensagem, concluido_em = NOW() WHERE id = :id"
        )->execute(['mensagem' => $mensagem, 'id' => $id]);

        Auditoria::registrar('falhou_geracao', 'evento_anais_geracoes', (int) $id, null, ['mensagem' => $mensagem]);
    }
}
