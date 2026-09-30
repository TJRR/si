<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 57: QUEM respondeu a pesquisa (nominal). E' a porta; os pontos sao a
 * linha de evento_bonus_creditos. Sao tabelas diferentes de proposito: a
 * porta precisa existir mesmo sem bonus cadastrado, e anular o credito nao
 * pode reabrir a pesquisa.
 *
 * Esta tabela nao tem nenhum elo com evento_pesquisa_respostas: nenhuma
 * chave estrangeira, nenhuma coluna em comum, nenhum caminho de juncao.
 *
 * Chaveada por USUARIO, e nao por inscricao (bloco E, pendencia 34): o
 * facilitador e o avaliador avulso entram no aplicativo sem nunca ter se
 * inscrito, e sao justamente quem mais tem o que dizer sobre a organizacao.
 * O credito de pontos continua exigindo inscricao, entao quem responde sem
 * ser inscrito responde e nao pontua.
 */
class PesquisaRespondenteRepository
{
    /**
     * Consultado pelo painel do participante, entao falha de banco devolve
     * "nao respondeu" em vez de derrubar a tela.
     */
    public function jaRespondeu($eventoId, $usuarioId)
    {
        return $this->buscarPorUsuario($eventoId, $usuarioId) !== null;
    }

    public function buscarPorUsuario($eventoId, $usuarioId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT * FROM evento_pesquisa_respondentes
                  WHERE evento_id = :evento AND usuario_id = :usuario LIMIT 1'
            );
            $stmt->execute(['evento' => (int) $eventoId, 'usuario' => (int) $usuarioId]);
            $linha = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('[Pesquisa] Falha ao conferir o respondente ' . (int) $usuarioId . ' do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return null;
        }

        return $linha !== false ? $linha : null;
    }

    /**
     * Gravado dentro da transacao aberta por PesquisaService::registrar().
     * O erro de chave repetida (estado 23000) e' quem barra, de verdade, a
     * segunda resposta da mesma pessoa, inclusive em envio simultaneo.
     */
    public function registrarNaTransacaoAtual($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_pesquisa_respondentes (evento_id, usuario_id, respondido_em)
             VALUES (:evento, :usuario, NOW())'
        );
        $stmt->execute(['evento' => (int) $eventoId, 'usuario' => (int) $usuarioId]);

        return (int) $pdo->lastInsertId();
    }

    public function contarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_pesquisa_respondentes WHERE evento_id = :evento');
        $stmt->execute(['evento' => (int) $eventoId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Inscritos do evento que ainda nao responderam. E' a lista do convite:
     * quem ja' respondeu nao recebe cobranca, e o segundo disparo fica
     * pequeno.
     *
     * So' inscritos, de proposito: a campanha de correio eletronico e'
     * montada a partir de evento_inscricoes. Facilitador e avaliador veem o
     * botao da pesquisa nas telas deles e nao recebem cobranca.
     */
    public function inscritosSemResposta($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.id, i.usuario_id, u.nome, u.email
               FROM evento_inscricoes i
               INNER JOIN usuarios u ON u.id = i.usuario_id
               LEFT JOIN evento_pesquisa_respondentes r
                      ON r.usuario_id = i.usuario_id AND r.evento_id = i.evento_id
              WHERE i.evento_id = :evento AND r.id IS NULL
              ORDER BY u.nome ASC'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Auditoria do fato nominal, chamada depois do commit. So' esta tabela e'
     * auditada: a gravacao das respostas nunca passa por Auditoria, porque a
     * trilha carimba usuario e instante e abriria sozinha o elo que o
     * desenho das tabelas fecha.
     */
    public function auditarResposta($eventoId, $usuarioId)
    {
        Auditoria::registrar(
            'responder_pesquisa_satisfacao',
            'evento_pesquisa_respondentes',
            (int) $usuarioId,
            null,
            ['evento_id' => (int) $eventoId]
        );
    }
}
