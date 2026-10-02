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
    /**
     * Fase 57 (bloco E): $incluirRemovidas com valor padrao false mantem o
     * comportamento de todos os chamadores anteriores, que perguntam "existe
     * presenca valida?". So' registrar() pede true, porque precisa enxergar a
     * linha marcada como removida para reativa-la em vez de tentar um segundo
     * INSERT que a chave unica recusaria.
     */
    public function buscarPorAtividadeEInscricao($atividadeId, $inscricaoId, $incluirRemovidas = false)
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM evento_checkins
                 WHERE atividade_id = :atividade_id AND evento_inscricao_id = :evento_inscricao_id';

        if (!$incluirRemovidas) {
            $sql .= ' AND removido_em IS NULL';
        }

        $stmt = $pdo->prepare($sql . ' LIMIT 1');
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
     *
     * Fase 57 (bloco E): a lista traz TAMBEM as presencas marcadas como
     * removidas, com quem removeu. Esta tela e' o unico lugar onde o
     * historico da correcao precisa aparecer; toda contagem e toda
     * exportacao filtram removido_em IS NULL.
     */
    public function listarPorAtividade($atividadeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, u.nome AS usuario_nome, u.email AS usuario_email,
                    (ai.id IS NOT NULL) AS inscricao_ativa,
                    r.nome AS removido_por_nome
             FROM evento_checkins c
             JOIN evento_inscricoes ei ON ei.id = c.evento_inscricao_id
             JOIN usuarios u ON u.id = ei.usuario_id
             LEFT JOIN evento_atividade_inscricoes ai
                    ON ai.atividade_id = c.atividade_id AND ai.evento_inscricao_id = c.evento_inscricao_id
             LEFT JOIN usuarios r ON r.id = c.removido_por
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
        $existente = $this->buscarPorAtividadeEInscricao($atividadeId, $inscricaoId, true);

        if ($existente !== null && $existente['removido_em'] === null) {
            return $existente;
        }

        $pdo = Database::conexao();

        // Fase 57 (bloco E, pendencia 33): presenca marcada como removida
        // e' REATIVADA, nunca duplicada - a chave unica (atividade_id,
        // evento_inscricao_id) recusaria um segundo INSERT, e sem este
        // caminho quem teve a presenca removida por engano nunca mais
        // conseguiria confirmar presenca naquela atividade. Mesmo desenho de
        // EventoAtividadeFacilitadorRepository::criar(). O horario passa a
        // ser o da leitura nova, porque e' ela que vale agora, inclusive
        // para a conta de presenca efetiva.
        if ($existente !== null) {
            $stmt = $pdo->prepare(
                'UPDATE evento_checkins
                    SET removido_em = NULL, removido_por = NULL, motivo_remocao = NULL,
                        checkin_em = NOW(), modalidade_acesso = :modalidade_acesso
                  WHERE id = :id'
            );
            $stmt->execute(['modalidade_acesso' => $modalidadeAcesso, 'id' => (int) $existente['id']]);

            $registro = $this->buscarPorAtividadeEInscricao($atividadeId, $inscricaoId);

            Auditoria::registrar('reativar_presenca', 'evento_checkins', (int) $existente['id'], $existente, [
                'checkin_em' => $registro['checkin_em'],
                'modalidade_acesso' => $modalidadeAcesso,
            ]);

            return $registro;
        }

        // Fase 57 (bloco E, pendencia 32): a conferencia acima e o INSERT
        // nao sao atomicos, e o chamador ainda repete a mesma conferencia
        // antes de chamar - duas leituras simultaneas do mesmo codigo
        // estouravam chave repetida sem tratamento, e como o endpoint ja'
        // escreveu o cabecalho da resposta, o leitor recebia uma resposta
        // quebrada. Mesmo tratamento do gemeo EstandeVisitaRepository::
        // registrar(): no erro 23000, quem perdeu a corrida devolve a linha
        // que a outra requisicao gravou, sem erro e sem auditar duas vezes.
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_checkins (atividade_id, evento_inscricao_id, checkin_em, modalidade_acesso) VALUES (:atividade_id, :evento_inscricao_id, NOW(), :modalidade_acesso)'
            );
            $stmt->execute(['atividade_id' => $atividadeId, 'evento_inscricao_id' => $inscricaoId, 'modalidade_acesso' => $modalidadeAcesso]);
            $id = (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->buscarPorAtividadeEInscricao($atividadeId, $inscricaoId);
            }

            throw $e;
        }

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
     * Fase 57 (bloco E, pendencia 33): marca a presenca como removida, sem
     * apagar a linha.
     *
     * Marcacao, e nunca DELETE, pelo motivo escrito na migration 184: a
     * presenca alimenta a coluna "Categoria" das duas exportacoes EJURR, que
     * podem ser geradas depois da atividade, e apagar tornaria um arquivo ja'
     * entregue irreproduzivel.
     *
     * Idempotente, como EventoAtividadeFacilitadorRepository::remover():
     * devolve false quando a presenca ja' estava removida, e quem chama
     * decide a mensagem.
     */
    public function remover($id, $usuarioId, $motivo)
    {
        $antes = $this->buscarPorId($id);

        if ($antes === null || $antes['removido_em'] !== null) {
            return false;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_checkins
                SET removido_em = NOW(), removido_por = :usuario, motivo_remocao = :motivo
              WHERE id = :id AND removido_em IS NULL'
        );
        $stmt->execute([
            'usuario' => (int) $usuarioId,
            'motivo' => $motivo,
            'id' => (int) $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        Auditoria::registrar('remover_presenca', 'evento_checkins', (int) $id, $antes, [
            'removido_por' => (int) $usuarioId,
            'motivo_remocao' => $motivo,
        ]);

        return true;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_checkins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
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
            'SELECT modalidade_acesso FROM evento_checkins
              WHERE evento_inscricao_id = :evento_inscricao_id AND removido_em IS NULL
              ORDER BY checkin_em DESC LIMIT 1'
        );
        $stmt->execute(['evento_inscricao_id' => $eventoInscricaoId]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado['modalidade_acesso'] : null;
    }

    /**
     * Metodo puro, sem banco - calcula a janela [abertura da leitura,
     * data_inicio + duracao x (tolerancia/100)] e devolve true so' se
     * $checkinEm cair dentro dela NOS DOIS LIMITES. Essa conta alimenta a
     * elegibilidade de certificado na Fase 59.
     *
     * Fase 58 (decisao do dono): o limite inferior deixou de ser data_inicio
     * e passou a ser a abertura da leitura (data_inicio menos
     * antecedencia_abertura_presenca). Com o limite antigo, quem chegava
     * antes do inicio e lia o codigo - exatamente quem a dinamica de pontos
     * premia com o extra de pontualidade - ficava marcado como "nao
     * efetivo". O limite inferior continua existindo, porque a leitura so'
     * e' aceita a partir da abertura (EventoAppController::validarPresenca()).
     */
    public function presencaEfetiva(array $atividade, $checkinEm)
    {
        $inicio = strtotime($atividade['data_inicio']);
        $fim = strtotime($atividade['data_fim']);
        $checkin = strtotime($checkinEm);
        $tolerancia = (int) $atividade['tolerancia_presenca_efetiva'];
        $abertura = $inicio - ((int) $atividade['antecedencia_abertura_presenca'] * 60);

        $duracaoSegundos = $fim - $inicio;
        $limiteSuperior = $inicio + (int) round($duracaoSegundos * ($tolerancia / 100));

        return $checkin >= $abertura && $checkin <= $limiteSuperior;
    }

    /**
     * Fase 58 (N2 do plano): desfaz a remocao feita pelo Administrador,
     * PRESERVANDO o horario original da leitura - ao contrario da
     * reativacao por nova leitura em registrar(), que troca o horario pelo
     * da leitura nova. E' o unico caminho de volta depois do fim da
     * atividade, porque a partir da Fase 58 a leitura fecha em data_fim.
     *
     * Idempotente: devolve false quando a presenca nao estava removida.
     */
    public function restaurar($id, $usuarioId)
    {
        $antes = $this->buscarPorId($id);

        if ($antes === null || $antes['removido_em'] === null) {
            return false;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_checkins
                SET removido_em = NULL, removido_por = NULL, motivo_remocao = NULL
              WHERE id = :id AND removido_em IS NOT NULL'
        );
        $stmt->execute(['id' => (int) $id]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        Auditoria::registrar('restaurar_presenca', 'evento_checkins', (int) $id, $antes, [
            'restaurado_por' => (int) $usuarioId,
        ]);

        return true;
    }
}
