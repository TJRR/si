<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 58: creditos de pontos por presenca em atividade (migration 187),
 * com os pontos, a regra e o fato congelados no instante da leitura.
 *
 * Nada aqui grava ou altera evento_checkins: o fato continua sendo daquela
 * tabela, e este repositorio so' le a presenca para condicionar a gravacao.
 *
 * Mesma distincao de anulacao de evento_bonus_creditos (Fase 57):
 * anulado_por NULO e' anulacao pelo sistema (presenca removida), que volta
 * sozinha; preenchido e' anulacao por pessoa, que nunca volta sozinha.
 */
class PresencaCreditoRepository
{
    /**
     * Grava o credito so' se a presenca ainda for valida no instante da
     * gravacao (o INSERT ... SELECT le evento_checkins com removido_em
     * nulo). Assim uma reconferencia que leu a lista de presencas antes de o
     * Administrador remover uma delas nao credita a presenca removida.
     *
     * A chave unica (atividade_id, evento_inscricao_id) torna a gravacao
     * idempotente: a perdedora de duas leituras simultaneas cai no ON
     * DUPLICATE KEY, que atribui a coluna a ela mesma e nao muda nada.
     * rowCount() devolve 1 na insercao e 0 quando nada foi gravado (linha
     * ja' existente ou presenca removida), como em BonusCreditoRepository.
     */
    public function creditar($eventoId, $checkinId, $pontosPresenca, $pontosPontualidade, $minutosAntesDoInicio, $antecedenciaExigida)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_presenca_creditos
                 (evento_id, checkin_id, atividade_id, evento_inscricao_id, pontos_presenca, pontos_pontualidade,
                  minutos_antes_do_inicio, antecedencia_exigida, creditado_em)
             SELECT :evento, c.id, c.atividade_id, c.evento_inscricao_id, :pontos_presenca, :pontos_pontualidade,
                    :minutos, :antecedencia, NOW()
               FROM evento_checkins c
              WHERE c.id = :checkin AND c.removido_em IS NULL
             ON DUPLICATE KEY UPDATE evento_id = evento_presenca_creditos.evento_id'
        );
        $stmt->execute([
            'evento' => (int) $eventoId,
            'pontos_presenca' => (int) $pontosPresenca,
            'pontos_pontualidade' => (int) $pontosPontualidade,
            'minutos' => (int) $minutosAntesDoInicio,
            'antecedencia' => $antecedenciaExigida !== null ? (int) $antecedenciaExigida : null,
            'checkin' => (int) $checkinId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function buscarPorAtividadeEInscricao($atividadeId, $inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_presenca_creditos WHERE atividade_id = :atividade AND evento_inscricao_id = :inscricao LIMIT 1'
        );
        $stmt->execute(['atividade' => (int) $atividadeId, 'inscricao' => (int) $inscricaoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Nova leitura depois de uma remocao por engano: EventoCheckinRepository::
     * registrar() troca o horario pelo da leitura nova ("e' ela que vale
     * agora"), entao o credito acompanha - pontos e pontualidade saem da
     * leitura nova, e a anulacao pelo sistema e' desfeita. So' age sobre
     * anulacao PELO SISTEMA; a feita por pessoa nunca volta por aqui.
     */
    public function recalcularAposNovaLeitura($id, $checkinId, $pontosPresenca, $pontosPontualidade, $minutosAntesDoInicio, $antecedenciaExigida)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_presenca_creditos
                SET checkin_id = :checkin, pontos_presenca = :pontos_presenca, pontos_pontualidade = :pontos_pontualidade,
                    minutos_antes_do_inicio = :minutos, antecedencia_exigida = :antecedencia, creditado_em = NOW(),
                    anulado_em = NULL, motivo_anulacao = NULL
              WHERE id = :id AND anulado_em IS NOT NULL AND anulado_por IS NULL'
        );
        $stmt->execute([
            'checkin' => (int) $checkinId,
            'pontos_presenca' => (int) $pontosPresenca,
            'pontos_pontualidade' => (int) $pontosPontualidade,
            'minutos' => (int) $minutosAntesDoInicio,
            'antecedencia' => $antecedenciaExigida !== null ? (int) $antecedenciaExigida : null,
            'id' => (int) $id,
        ]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar('recalcular_credito_presenca', 'evento_presenca_creditos', (int) $id, null, [
                'pontos_presenca' => (int) $pontosPresenca,
                'pontos_pontualidade' => (int) $pontosPontualidade,
                'minutos_antes_do_inicio' => (int) $minutosAntesDoInicio,
            ]);
        }

        return $alterou;
    }

    public function anularPeloSistema($id, $motivo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_presenca_creditos
                SET anulado_em = NOW(), anulado_por = NULL, motivo_anulacao = :motivo
              WHERE id = :id AND anulado_em IS NULL'
        );
        $stmt->execute(['motivo' => $motivo, 'id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar('anular_credito_presenca_pelo_sistema', 'evento_presenca_creditos', (int) $id, null, [
                'motivo_anulacao' => $motivo,
            ]);
        }

        return $alterou;
    }

    /**
     * Restauracao de presenca pelo Administrador (EventoCheckinRepository::
     * restaurar(), que preserva o horario original): o credito volta como
     * era, sem recalculo. So' desfaz anulacao pelo sistema.
     */
    public function reverterAnulacaoAutomatica($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_presenca_creditos
                SET anulado_em = NULL, motivo_anulacao = NULL
              WHERE id = :id AND anulado_em IS NOT NULL AND anulado_por IS NULL'
        );
        $stmt->execute(['id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar('reverter_anulacao_presenca_pelo_sistema', 'evento_presenca_creditos', (int) $id, null, [
                'motivo' => 'A presença foi restaurada pela organização.',
            ]);
        }

        return $alterou;
    }

    /**
     * Creditos de uma atividade, na forma [evento_inscricao_id => linha],
     * para as colunas de pontos da tela de Presencas.
     */
    public function mapaPorAtividade($atividadeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_presenca_creditos WHERE atividade_id = :atividade');
        $stmt->execute(['atividade' => (int) $atividadeId]);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['evento_inscricao_id']] = $linha;
        }

        return $mapa;
    }

    /**
     * Extrato de presencas pontuadas de uma pessoa, com o nome da atividade.
     * Traz tambem as anuladas, que o extrato mostra com o motivo.
     */
    public function listarDaInscricao($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT pc.*, a.nome AS atividade_nome, a.data_inicio AS atividade_inicio
               FROM evento_presenca_creditos pc
               INNER JOIN evento_atividades a ON a.id = pc.atividade_id
              WHERE pc.evento_inscricao_id = :inscricao
              ORDER BY a.data_inicio ASC, pc.id ASC'
        );
        $stmt->execute(['inscricao' => (int) $inscricaoId]);

        return $stmt->fetchAll();
    }

    /**
     * Presencas validas do evento que ainda nao tem credito, com o que a
     * reconferencia precisa para calcular: horarios da atividade, pontos da
     * atividade e do tipo, e se a pessoa e' facilitadora ativa da atividade
     * (Fase 58, N1: essa presenca nao pontua).
     */
    public function presencasSemCredito($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.id AS checkin_id, c.atividade_id, c.evento_inscricao_id, c.checkin_em,
                    a.data_inicio, a.data_fim, a.pontos_presenca AS atividade_pontos_presenca,
                    a.pontos_pontualidade AS atividade_pontos_pontualidade,
                    t.pontos_presenca AS tipo_pontos_presenca, t.pontos_pontualidade AS tipo_pontos_pontualidade,
                    EXISTS (
                        SELECT 1 FROM evento_atividade_facilitadores f
                         WHERE f.atividade_id = c.atividade_id AND f.usuario_id = ei.usuario_id AND f.removido_em IS NULL
                    ) AS eh_facilitador
               FROM evento_checkins c
               INNER JOIN evento_atividades a ON a.id = c.atividade_id
               INNER JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
               LEFT JOIN evento_atividade_tipos t ON t.id = a.tipo_id
               LEFT JOIN evento_presenca_creditos pc
                      ON pc.atividade_id = c.atividade_id AND pc.evento_inscricao_id = c.evento_inscricao_id
              WHERE a.evento_id = :evento AND c.removido_em IS NULL AND pc.id IS NULL'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        return $stmt->fetchAll();
    }
}
