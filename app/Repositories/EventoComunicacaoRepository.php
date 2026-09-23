<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 45: aviso em massa aos inscritos de um evento (sub-aba
 * "Comunicacao"). Uma campanha (evento_comunicacoes) gera uma fila de
 * destinatarios (evento_comunicacao_destinatarios), processada aos poucos
 * pelo script database/processar_comunicacao_evento.php - ver esse arquivo
 * para o mecanismo de lote (10 por execucao de cron, a cada 1 minuto).
 */
class EventoComunicacaoRepository
{
    /**
     * Insere a campanha e toda a fila de destinatarios numa unica
     * transacao - se qualquer INSERT falhar no meio, desfaz tudo, evitando
     * uma campanha "orfa" com total_destinatarios maior que as linhas reais
     * gravadas na fila.
     */
    public function criarCampanhaComDestinatarios(array $dados, array $inscricaoIds)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_comunicacoes (evento_id, autor_usuario_id, assunto, corpo_html, total_destinatarios)
                 VALUES (:evento_id, :autor_usuario_id, :assunto, :corpo_html, :total_destinatarios)'
            );
            $stmt->execute([
                'evento_id' => $dados['evento_id'],
                'autor_usuario_id' => $dados['autor_usuario_id'],
                'assunto' => $dados['assunto'],
                'corpo_html' => $dados['corpo_html'],
                'total_destinatarios' => count($inscricaoIds),
            ]);
            $comunicacaoId = (int) $pdo->lastInsertId();

            $stmtDestinatario = $pdo->prepare(
                'INSERT INTO evento_comunicacao_destinatarios (comunicacao_id, evento_inscricao_id)
                 VALUES (:comunicacao_id, :evento_inscricao_id)'
            );

            foreach ($inscricaoIds as $inscricaoId) {
                $stmtDestinatario->execute([
                    'comunicacao_id' => $comunicacaoId,
                    'evento_inscricao_id' => $inscricaoId,
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('criar', 'evento_comunicacoes', $comunicacaoId, null, $dados + ['destinatarios' => count($inscricaoIds)]);

        return $comunicacaoId;
    }

    /**
     * Fase 51: campanha para destinatarios avulsos, isto e, pessoas que
     * ainda nao tem inscricao no evento - o caso dos autores trazidos do
     * formulario externo, que ganham conta mas nunca se inscreveram. Mesma
     * fila, mesmo lote de 10 por execucao do agendador: e' isso que evita
     * repetir o problema que a fila foi criada para resolver, disparando
     * dezenas de e-mails de uma vez pelo canal institucional.
     *
     * $destinatarios: lista de ['usuario_id', 'email', 'nome',
     * 'token_senha_id' (opcional, para o convite com link de definir senha)].
     */
    public function criarCampanhaAvulsa(array $dados, array $destinatarios)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_comunicacoes (evento_id, autor_usuario_id, tipo, assunto, corpo_html, total_destinatarios)
                 VALUES (:evento_id, :autor_usuario_id, :tipo, :assunto, :corpo_html, :total_destinatarios)'
            );
            $stmt->execute([
                'evento_id' => $dados['evento_id'],
                'autor_usuario_id' => $dados['autor_usuario_id'],
                'tipo' => $dados['tipo'],
                'assunto' => $dados['assunto'],
                'corpo_html' => $dados['corpo_html'],
                'total_destinatarios' => count($destinatarios),
            ]);
            $comunicacaoId = (int) $pdo->lastInsertId();

            $stmtDestinatario = $pdo->prepare(
                'INSERT INTO evento_comunicacao_destinatarios (comunicacao_id, usuario_id, email, nome, token_senha_id)
                 VALUES (:comunicacao_id, :usuario_id, :email, :nome, :token_senha_id)'
            );

            foreach ($destinatarios as $destinatario) {
                $stmtDestinatario->execute([
                    'comunicacao_id' => $comunicacaoId,
                    'usuario_id' => isset($destinatario['usuario_id']) ? $destinatario['usuario_id'] : null,
                    'email' => $destinatario['email'],
                    'nome' => isset($destinatario['nome']) ? $destinatario['nome'] : null,
                    'token_senha_id' => isset($destinatario['token_senha_id']) ? $destinatario['token_senha_id'] : null,
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('criar', 'evento_comunicacoes', $comunicacaoId, null, $dados + ['destinatarios' => count($destinatarios)]);

        return $comunicacaoId;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_comunicacoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $comunicacao = $stmt->fetch();

        return $comunicacao !== false ? $comunicacao : null;
    }

    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ec.*, u.nome AS autor_nome
             FROM evento_comunicacoes ec
             JOIN usuarios u ON u.id = ec.autor_usuario_id
             WHERE ec.evento_id = :evento_id
             ORDER BY ec.criado_em DESC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Ate' $limite destinatarios pendentes no total, das campanhas mais
     * antigas primeiro - e' este LIMIT global (nao por campanha) que impoe
     * o lote de 10 por execucao do cron, junto com o intervalo de 1 minuto
     * entre execucoes (ver database/processar_comunicacao_evento.php).
     */
    public function proximosPendentes($limite = 10)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT ecd.id AS destinatario_id, ec.id AS comunicacao_id, ec.tipo, ec.assunto, ec.corpo_html,
                    ec.evento_id,
                    COALESCE(ua.nome, ui.nome, ecd.nome) AS usuario_nome,
                    COALESCE(ua.email, ui.email, ecd.email) AS usuario_email,
                    ts.token AS token_senha
             FROM evento_comunicacao_destinatarios ecd
             JOIN evento_comunicacoes ec ON ec.id = ecd.comunicacao_id
             LEFT JOIN evento_inscricoes ei ON ei.id = ecd.evento_inscricao_id
             LEFT JOIN usuarios ui ON ui.id = ei.usuario_id
             LEFT JOIN usuarios ua ON ua.id = ecd.usuario_id
             LEFT JOIN tokens_senha ts ON ts.id = ecd.token_senha_id
             WHERE ecd.status = "pendente"
             ORDER BY ec.criado_em ASC, ecd.id ASC
             LIMIT ' . (int) $limite
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function marcarProcessado($destinatarioId, $sucesso)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_comunicacao_destinatarios
             SET status = :status, processado_em = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => $sucesso ? 'enviado' : 'falhou',
            'id' => $destinatarioId,
        ]);
    }

    /**
     * Recalcula os contadores da campanha a partir da fila (fonte da
     * verdade) e marca concluido_em quando nao sobra nenhum pendente -
     * chamado depois de cada lote processado pelo script do cron.
     */
    public function atualizarContadoresEConcluir($comunicacaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT
                SUM(status = "enviado") AS enviados,
                SUM(status = "falhou") AS falhas,
                SUM(status = "pendente") AS pendentes
             FROM evento_comunicacao_destinatarios
             WHERE comunicacao_id = :comunicacao_id'
        );
        $stmt->execute(['comunicacao_id' => $comunicacaoId]);
        $contagem = $stmt->fetch();

        $stmtAtualizar = $pdo->prepare(
            'UPDATE evento_comunicacoes
             SET total_enviados = :enviados, total_falhas = :falhas,
                 concluido_em = IF(:pendentes = 0, NOW(), concluido_em)
             WHERE id = :id'
        );
        $stmtAtualizar->execute([
            'enviados' => (int) $contagem['enviados'],
            'falhas' => (int) $contagem['falhas'],
            'pendentes' => (int) $contagem['pendentes'],
            'id' => $comunicacaoId,
        ]);
    }

    /**
     * Nome/e-mail de quem falhou numa campanha - exibido no historico da
     * sub-aba Comunicacao para o Admin saber quem desmarcar ao compor um
     * reenvio manual (nao ha reprocessamento automatico de falhas).
     */
    public function listarFalhasPorComunicacao($comunicacaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT u.nome AS usuario_nome, u.email AS usuario_email
             FROM evento_comunicacao_destinatarios ecd
             JOIN evento_inscricoes ei ON ei.id = ecd.evento_inscricao_id
             JOIN usuarios u ON u.id = ei.usuario_id
             WHERE ecd.comunicacao_id = :comunicacao_id AND ecd.status = "falhou"
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['comunicacao_id' => $comunicacaoId]);

        return $stmt->fetchAll();
    }
}
