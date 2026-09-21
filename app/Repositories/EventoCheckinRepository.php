<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 47: presenca bruta (horario exato) em uma Atividade, confirmada pelo
 * proprio participante lendo o codigo fixo dela. Sem status/classificacao
 * persistida - "presenca efetiva" e' sempre calculada em tempo de leitura
 * (ver presencaEfetiva()), nunca gravada, para nao ficar desatualizada se o
 * Admin mudar a tolerancia da atividade depois do check-in ja' ter
 * acontecido. evento_inscricao_id (Evento), nunca atividade_inscricao_id
 * (Atividade) - presenca ja' ocorrida e' fato historico e nao desaparece se
 * a pessoa cancelar a inscricao na atividade depois (ver listarPorAtividade()).
 */
class EventoCheckinRepository
{
    public function buscarPorAtividadeEInscricao($atividadeId, $inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_checkins WHERE atividade_id = :atividade_id AND evento_inscricao_id = :evento_inscricao_id LIMIT 1'
        );
        $stmt->execute(['atividade_id' => $atividadeId, 'evento_inscricao_id' => $inscricaoId]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    /**
     * LEFT JOIN ate' evento_atividade_inscricoes (pelo mesmo par
     * atividade/inscricao) so' para a tela "Presencas" saber se a inscricao
     * na atividade ainda existe - evento_checkins nao referencia essa
     * tabela, entao alguem pode ter confirmado presenca e depois cancelado a
     * inscricao na atividade; a tela precisa mostrar isso, nao esconder.
     */
    public function listarPorAtividade($atividadeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, u.nome AS usuario_nome, u.email AS usuario_email,
                    (ai.id IS NOT NULL) AS inscricao_ativa
             FROM evento_checkins c
             JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
             JOIN usuarios u ON u.id = ei.usuario_id
             LEFT JOIN evento_atividade_inscricoes ai
                    ON ai.atividade_id = c.atividade_id AND ai.evento_inscricao_id = c.evento_inscricao_id
             WHERE c.atividade_id = :atividade_id
             ORDER BY c.checkin_em ASC'
        );
        $stmt->execute(['atividade_id' => $atividadeId]);

        return $stmt->fetchAll();
    }

    /**
     * Idempotente: se ja' existe check-in para o par, devolve o registro
     * existente sem inserir de novo nem auditar. Chamador (EventoAppController::
     * validarPresenca()) e' quem decide a mensagem de sucesso em cada caso.
     *
     * Fase 48: $modalidadeAcesso ('presencial' se leu o QR de 6 caracteres,
     * 'online' se digitou o codigo de 5) - necessario porque atividade
     * hibrida aceita os dois caminhos, e a exportacao EJURR/visao geral do
     * Evento precisam saber qual foi usado em cada check-in.
     */
    public function registrar($atividadeId, $inscricaoId, $modalidadeAcesso = 'presencial')
    {
        $existente = $this->buscarPorAtividadeEInscricao($atividadeId, $inscricaoId);

        if ($existente !== null) {
            return $existente;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_checkins (atividade_id, evento_inscricao_id, checkin_em, modalidade_acesso) VALUES (:atividade_id, :evento_inscricao_id, NOW(), :modalidade_acesso)'
        );
        $stmt->execute(['atividade_id' => $atividadeId, 'evento_inscricao_id' => $inscricaoId, 'modalidade_acesso' => $modalidadeAcesso]);
        $id = (int) $pdo->lastInsertId();

        $registro = $this->buscarPorAtividadeEInscricao($atividadeId, $inscricaoId);

        Auditoria::registrar('confirmar_presenca', 'evento_checkins', $id, null, [
            'atividade_id' => $atividadeId,
            'evento_inscricao_id' => $inscricaoId,
            'checkin_em' => $registro['checkin_em'],
            'modalidade_acesso' => $modalidadeAcesso,
        ]);

        return $registro;
    }

    /**
     * Fase 48: modalidade do check-in mais recente da pessoa em QUALQUER
     * atividade do evento - usado pela exportacao EJURR na visao geral do
     * Evento (coluna "Categoria"). null se a pessoa nunca confirmou
     * presenca em nenhuma atividade do evento.
     */
    public function modalidadeMaisRecenteNoEvento($eventoInscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT modalidade_acesso FROM evento_checkins WHERE evento_inscricao_id = :evento_inscricao_id ORDER BY checkin_em DESC LIMIT 1'
        );
        $stmt->execute(['evento_inscricao_id' => $eventoInscricaoId]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado['modalidade_acesso'] : null;
    }

    /**
     * Metodo puro, sem banco - calcula a janela [data_inicio, data_inicio +
     * duracao x (tolerancia/100)] e devolve true so' se $checkinEm cair
     * dentro dela NOS DOIS LIMITES. O limite inferior e' obrigatorio: a
     * aceitacao do check-in em si e' ampla (qualquer momento ate data_fim,
     * inclusive antes de data_inicio), mas sem o limite inferior aqui,
     * alguem confirmando presenca antes da atividade comecar contaria
     * indevidamente como presenca efetiva - e essa conta alimenta direto a
     * elegibilidade de certificado na Fase 54.
     */
    public function presencaEfetiva(array $atividade, $checkinEm)
    {
        $inicio = strtotime($atividade['data_inicio']);
        $fim = strtotime($atividade['data_fim']);
        $checkin = strtotime($checkinEm);
        $tolerancia = (int) $atividade['tolerancia_presenca_efetiva'];

        $duracaoSegundos = $fim - $inicio;
        $limiteSuperior = $inicio + (int) round($duracaoSegundos * ($tolerancia / 100));

        return $checkin >= $inicio && $checkin <= $limiteSuperior;
    }
}
