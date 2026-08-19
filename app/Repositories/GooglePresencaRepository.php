<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 32: sessoes brutas de presenca no Google Meet, capturadas pelo cron
 * (database/capturar_presenca_google_meet.php). Uma linha por trecho de
 * entrada/saida - quem cai da chamada e volta gera varias linhas, e a duracao
 * final e' a SOMA delas.
 *
 * Sem Auditoria::registrar aqui: e' sincronizacao automatica de estado
 * externo, nao acao de usuario - mesma regra ja aplicada em
 * GoogleConviteStatusRepository (Fase 31).
 */
class GooglePresencaRepository
{
    /**
     * Regrava as sessoes de um horario. IDEMPOTENTE de proposito: apaga o que
     * havia antes de inserir. Essa e' a defesa PRIMARIA contra duplicacao de
     * carga horaria - o filtro presenca_status='pendente' do cron nao protege
     * sozinho, porque duas execucoes sobrepostas leem 'pendente' antes de
     * qualquer uma gravar 'capturada'. Assim, reprocessar o mesmo horario
     * (pelo botao "Reprocessar presenca" ou por rajada de cron) e' inofensivo
     * por construcao.
     *
     * $participantes: [['meet_ref','nome_bruto','tipo_origem','participante_id','sessoes'=>[['inicio','fim']]], ...]
     */
    public function salvarSessoes($tipo, $horarioId, array $participantes)
    {
        $pdo = Database::conexao();

        $this->removerPorHorario($tipo, $horarioId);

        $stmt = $pdo->prepare(
            'INSERT INTO google_presenca_sessoes
                (tipo, horario_id, participante_meet_ref, nome_bruto, tipo_origem, participante_id, inicio, fim)
             VALUES (:tipo, :horario_id, :meet_ref, :nome_bruto, :tipo_origem, :participante_id, :inicio, :fim)'
        );

        foreach ($participantes as $participante) {
            foreach ($participante['sessoes'] as $sessao) {
                $stmt->execute([
                    'tipo' => $tipo,
                    'horario_id' => $horarioId,
                    'meet_ref' => $participante['meet_ref'],
                    'nome_bruto' => $participante['nome_bruto'],
                    'tipo_origem' => $participante['tipo_origem'],
                    'participante_id' => $participante['participante_id'],
                    'inicio' => $sessao['inicio'],
                    'fim' => $sessao['fim'],
                ]);
            }
        }
    }

    /**
     * Uma linha por pessoa que entrou, com a duracao total somada.
     *
     * Agrupa por participante_meet_ref (referencia opaca da Meet API), NUNCA
     * por nome_bruto: depois do expurgo dos 30 dias o nome vira NULL, e
     * agrupar por ele fundiria todos os intrusos anonimizados de um horario
     * numa linha so', somando duracoes de pessoas distintas. Pela referencia,
     * contagem e duracoes individuais sobrevivem ao expurgo - some so' o nome.
     *
     * duracao_segundos vem NULL quando nenhuma sessao da pessoa tem fim
     * registrado (SUM ignora NULL, e se todas forem NULL o total e' NULL) -
     * a tela mostra "nao apurada", nunca zero.
     * participante_id NULL = nao casou com nenhum convidado = intruso.
     */
    public function listarAgregadoPorHorario($tipo, $horarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT s.participante_meet_ref,
                    s.participante_id,
                    MAX(s.nome_bruto) AS nome_bruto,
                    MAX(s.tipo_origem) AS tipo_origem,
                    COUNT(*) AS total_sessoes,
                    MIN(s.inicio) AS primeira_entrada,
                    SUM(TIMESTAMPDIFF(SECOND, s.inicio, s.fim)) AS duracao_segundos,
                    p.nome AS participante_nome
             FROM google_presenca_sessoes s
             LEFT JOIN participantes p ON p.id = s.participante_id
             WHERE s.tipo = :tipo AND s.horario_id = :horario_id
             GROUP BY s.participante_meet_ref, s.participante_id, p.nome
             ORDER BY p.nome IS NULL, p.nome, nome_bruto'
        );
        $stmt->execute(['tipo' => $tipo, 'horario_id' => $horarioId]);

        return $stmt->fetchAll();
    }

    public function removerPorHorario($tipo, $horarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM google_presenca_sessoes WHERE tipo = :tipo AND horario_id = :horario_id');
        $stmt->execute(['tipo' => $tipo, 'horario_id' => $horarioId]);
    }

    /**
     * Politica de retencao (Fase 32): o nome de exibicao so' faz sentido
     * enquanto alguem ainda pode revisar aquele horario. Passados 30 dias a
     * linha e' anonimizada - duracao, contagem de intrusos e vinculo com
     * participante continuam intactos. Usado por
     * database/expurgar_nomes_presenca.php.
     */
    public function contarNomesExpiraveis($dias = 30)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM google_presenca_sessoes
             WHERE nome_bruto IS NOT NULL AND capturado_em < (NOW() - INTERVAL :dias DAY)'
        );
        $stmt->bindValue('dias', (int) $dias, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function expurgarNomes($dias = 30)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE google_presenca_sessoes SET nome_bruto = NULL
             WHERE nome_bruto IS NOT NULL AND capturado_em < (NOW() - INTERVAL :dias DAY)'
        );
        $stmt->bindValue('dias', (int) $dias, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
