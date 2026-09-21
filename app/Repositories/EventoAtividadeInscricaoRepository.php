<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 46: inscricao (opcional, por atividade) de quem ja' esta' inscrito no
 * Evento (evento_inscricao_id, nunca usuario_id direto) numa Atividade
 * especifica dele. Sem status 'cancelada' - cancelar() e' DELETE da linha,
 * mesmo espirito do resto do sistema (nenhuma tabela faz soft-delete;
 * Auditoria e' a trilha historica).
 */
class EventoAtividadeInscricaoRepository
{
    /**
     * Join ate' usuarios/evento_atividades - usado por
     * AtividadeAdminController::confirmarEspera() pra notificar o
     * participante (e-mail/nome) e saber o atividade_id do redirect, sem
     * precisar de uma segunda consulta.
     */
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.*, u.id AS usuario_id, u.nome AS usuario_nome, u.email AS usuario_email, a.nome AS atividade_nome, a.evento_id
             FROM evento_atividade_inscricoes i
             JOIN evento_inscricoes ei ON ei.id = i.evento_inscricao_id
             JOIN usuarios u ON u.id = ei.usuario_id
             JOIN evento_atividades a ON a.id = i.atividade_id
             WHERE i.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    public function buscarPorAtividadeEInscricao($atividadeId, $inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_atividade_inscricoes WHERE atividade_id = :atividade_id AND evento_inscricao_id = :evento_inscricao_id LIMIT 1'
        );
        $stmt->execute(['atividade_id' => $atividadeId, 'evento_inscricao_id' => $inscricaoId]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    public function listarPorAtividade($atividadeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.*, u.nome AS usuario_nome, u.email AS usuario_email
             FROM evento_atividade_inscricoes i
             JOIN evento_inscricoes ei ON ei.id = i.evento_inscricao_id
             JOIN usuarios u ON u.id = ei.usuario_id
             WHERE i.atividade_id = :atividade_id
             ORDER BY i.inscrito_em ASC'
        );
        $stmt->execute(['atividade_id' => $atividadeId]);

        return $stmt->fetchAll();
    }

    public function listarPorInscricao($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_atividade_inscricoes WHERE evento_inscricao_id = :evento_inscricao_id');
        $stmt->execute(['evento_inscricao_id' => $inscricaoId]);

        return $stmt->fetchAll();
    }

    /**
     * Transacao com SELECT ... FOR UPDATE na atividade, pra nao deixar duas
     * inscricoes concorrentes lerem a mesma contagem de vagas e as duas
     * decidirem "confirmada" na ultima vaga. Checa idempotencia (duplo
     * clique) ANTES de decidir status - se ja existe linha para este par,
     * devolve o status ja gravado sem tentar INSERT de novo, sem depender da
     * UNIQUE KEY estourar excecao como fluxo de controle. Retorna
     * 'confirmada' | 'espera' | 'lotada' | 'ja_confirmada' | 'ja_espera'.
     */
    public function inscrever($atividadeId, $inscricaoId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT vagas, permite_lista_espera FROM evento_atividades WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $atividadeId]);
            $atividade = $stmt->fetch();

            $stmtExistente = $pdo->prepare(
                'SELECT status FROM evento_atividade_inscricoes WHERE atividade_id = :atividade_id AND evento_inscricao_id = :evento_inscricao_id LIMIT 1'
            );
            $stmtExistente->execute(['atividade_id' => $atividadeId, 'evento_inscricao_id' => $inscricaoId]);
            $existente = $stmtExistente->fetch();

            if ($existente !== false) {
                $pdo->commit();

                return $existente['status'] === 'confirmada' ? 'ja_confirmada' : 'ja_espera';
            }

            $status = 'confirmada';

            if ($atividade['vagas'] !== null) {
                $stmtContagem = $pdo->prepare(
                    "SELECT COUNT(*) AS total FROM evento_atividade_inscricoes WHERE atividade_id = :atividade_id AND status = 'confirmada'"
                );
                $stmtContagem->execute(['atividade_id' => $atividadeId]);
                $totalConfirmadas = (int) $stmtContagem->fetch()['total'];

                if ($totalConfirmadas >= (int) $atividade['vagas']) {
                    if (!$atividade['permite_lista_espera']) {
                        $pdo->rollBack();

                        return 'lotada';
                    }

                    $status = 'espera';
                }
            }

            $stmtInsert = $pdo->prepare(
                'INSERT INTO evento_atividade_inscricoes (atividade_id, evento_inscricao_id, status) VALUES (:atividade_id, :evento_inscricao_id, :status)'
            );
            $stmtInsert->execute(['atividade_id' => $atividadeId, 'evento_inscricao_id' => $inscricaoId, 'status' => $status]);
            $id = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('inscrever', 'evento_atividade_inscricoes', $id, null, [
            'atividade_id' => $atividadeId,
            'evento_inscricao_id' => $inscricaoId,
            'status' => $status,
        ]);

        return $status;
    }

    public function cancelar($atividadeId, $inscricaoId)
    {
        $registro = $this->buscarPorAtividadeEInscricao($atividadeId, $inscricaoId);

        if ($registro === null) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_atividade_inscricoes WHERE id = :id');
        $stmt->execute(['id' => $registro['id']]);

        Auditoria::registrar('cancelar', 'evento_atividade_inscricoes', $registro['id'], $registro, null);
    }

    /**
     * Idempotente, sem reconferir capacidade contra vagas - decisao manual
     * do Admin tem prioridade (mesma filosofia de
     * EventoInscricaoRepository::homologar()); a ocupacao fica visivel na
     * tela admin/atividades/inscritos.php antes do clique.
     */
    public function confirmarDaEspera($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare("UPDATE evento_atividade_inscricoes SET status = 'confirmada' WHERE id = :id AND status = 'espera'");
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            Auditoria::registrar('confirmar_espera', 'evento_atividade_inscricoes', $id, ['status' => 'espera'], ['status' => 'confirmada']);
        }
    }
}
