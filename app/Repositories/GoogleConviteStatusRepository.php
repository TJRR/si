<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 31: cache local do RSVP (aceite/recusa) de cada convidado no evento
 * do Google, atualizado a partir da propria resposta da Calendar API
 * (attendees[].responseStatus) sempre que o evento e' lido - nunca uma
 * chamada extra so' pra isso (ver App\Services\GoogleCalendarSyncService).
 * Sem Auditoria::registrar aqui: e' sincronizacao automatica de estado
 * externo, nao uma acao de usuario.
 */
class GoogleConviteStatusRepository
{
    /**
     * Upsert do status de cada e-mail retornado pelo Google, e limpeza de
     * quem nao esta mais na lista atual de attendees (equipe removida do
     * horario desde a ultima sincronizacao).
     */
    public function sincronizar($tipo, $horarioId, array $statusPorEmail, array $participanteIdPorEmail = [])
    {
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'INSERT INTO google_convite_status (tipo, horario_id, email, participante_id, status)
             VALUES (:tipo, :horario_id, :email, :participante_id, :status)
             ON DUPLICATE KEY UPDATE status = :status_atualizado, participante_id = :participante_id_atualizado'
        );

        foreach ($statusPorEmail as $email => $status) {
            $participanteId = isset($participanteIdPorEmail[$email]) ? $participanteIdPorEmail[$email] : null;

            $stmt->execute([
                'tipo' => $tipo,
                'horario_id' => $horarioId,
                'email' => $email,
                'participante_id' => $participanteId,
                'status' => $status,
                'status_atualizado' => $status,
                'participante_id_atualizado' => $participanteId,
            ]);
        }

        $this->removerAusentes($tipo, $horarioId, array_keys($statusPorEmail));
    }

    public function removerAusentes($tipo, $horarioId, array $emailsAtuais)
    {
        if (empty($emailsAtuais)) {
            $this->removerPorHorario($tipo, $horarioId);
            return;
        }

        $pdo = Database::conexao();
        $placeholders = implode(',', array_fill(0, count($emailsAtuais), '?'));
        $parametros = array_merge([$tipo, $horarioId], $emailsAtuais);

        $pdo->prepare(
            "DELETE FROM google_convite_status WHERE tipo = ? AND horario_id = ? AND email NOT IN ($placeholders)"
        )->execute($parametros);
    }

    public function removerPorHorario($tipo, $horarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM google_convite_status WHERE tipo = :tipo AND horario_id = :horario_id');
        $stmt->execute(['tipo' => $tipo, 'horario_id' => $horarioId]);
    }

    /**
     * Lista com nome do participante (quando disponivel) para o selo de
     * RSVP por integrante convidado na listagem admin.
     */
    public function listarComNomePorHorario($tipo, $horarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT g.email, g.status, g.atualizado_em, g.participante_id, p.nome AS participante_nome
             FROM google_convite_status g
             LEFT JOIN participantes p ON p.id = g.participante_id
             WHERE g.tipo = :tipo AND g.horario_id = :horario_id
             ORDER BY p.nome, g.email'
        );
        $stmt->execute(['tipo' => $tipo, 'horario_id' => $horarioId]);

        return $stmt->fetchAll();
    }
}
