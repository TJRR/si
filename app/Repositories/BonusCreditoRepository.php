<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 57: creditos de bonus, com os pontos congelados no valor vigente no
 * instante em que a condicao se cumpriu - mesma regra de
 * evento_estande_visitas.pontos_creditados (Fase 54), evento_conexoes (55) e
 * evento_divulgacao_comprovacoes (56). Mudar os pontos do bonus depois nao
 * altera quem ja' recebeu.
 *
 * Este repositorio nunca abre transacao: creditar() e' idempotente pela
 * chave unica (evento_inscricao_id, bonus_id), entao duas apuracoes
 * simultaneas da mesma pessoa nao se atrapalham e nao precisam de bloqueio.
 */
class BonusCreditoRepository
{
    /**
     * Grava o credito se a pessoa ainda nao tiver aquele bonus. Devolve true
     * quando inseriu e false quando ja' existia.
     *
     * O ON DUPLICATE KEY UPDATE atribui a coluna a ela mesma de proposito: e'
     * uma gravacao que nao muda nada, so' evita o erro de chave repetida.
     * Sem MYSQL_ATTR_FOUND_ROWS (o projeto nao o liga em Database.php),
     * rowCount() devolve 1 na insercao e 0 quando a linha ja' existia, que e'
     * exatamente a resposta de que a apuracao precisa.
     */
    public function creditar($eventoId, $bonusId, $inscricaoId, $pontos, $exigenciaAtingida)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_bonus_creditos
                 (evento_id, bonus_id, evento_inscricao_id, pontos_creditados, exigencia_atingida, creditado_em)
             VALUES (:evento, :bonus, :inscricao, :pontos, :exigencia, NOW())
             ON DUPLICATE KEY UPDATE evento_id = evento_id'
        );
        $stmt->execute([
            'evento' => (int) $eventoId,
            'bonus' => (int) $bonusId,
            'inscricao' => (int) $inscricaoId,
            'pontos' => (int) $pontos,
            'exigencia' => (int) $exigenciaAtingida,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Identificadores dos bonus que a pessoa ja' tem, INCLUSIVE os anulados.
     * Linha anulada tambem bloqueia credito novo, ao contrario da Fase 56:
     * aqui a condicao continua cumprida para sempre, entao recreditar
     * desfaria a anulacao na leitura seguinte.
     */
    public function bonusJaCreditados($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT bonus_id FROM evento_bonus_creditos WHERE evento_inscricao_id = :inscricao');
        $stmt->execute(['inscricao' => (int) $inscricaoId]);

        $ids = [];

        foreach ($stmt->fetchAll() as $linha) {
            $ids[] = (int) $linha['bonus_id'];
        }

        return $ids;
    }

    /**
     * O mesmo, para o evento inteiro, na forma
     * [evento_inscricao_id => [bonus_id => ['id', 'anulado_em', 'anulado_por']]].
     * Uma consulta so', usada pela reapuracao em lote: alem de saber quem ja'
     * tem cada bonus, ela precisa distinguir a anulacao humana (definitiva)
     * da anulacao pelo sistema (que volta quando a condicao volta).
     */
    public function creditadosPorInscricaoNoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT id, evento_inscricao_id, bonus_id, anulado_em, anulado_por
               FROM evento_bonus_creditos WHERE evento_id = :evento'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $inscricao = (int) $linha['evento_inscricao_id'];

            if (!isset($mapa[$inscricao])) {
                $mapa[$inscricao] = [];
            }

            $mapa[$inscricao][(int) $linha['bonus_id']] = [
                'id' => (int) $linha['id'],
                'anulado_em' => $linha['anulado_em'],
                'anulado_por' => $linha['anulado_por'] !== null ? (int) $linha['anulado_por'] : null,
            ];
        }

        return $mapa;
    }

    /**
     * Resumo do participante para o painel. Recurso opcional da tela do
     * inscrito: falha de banco devolve zeros e lista vazia, entao tabela
     * ainda nao criada nunca derruba o painel.
     */
    public function resumoParticipante($inscricaoId)
    {
        $vazio = ['total_bonus' => 0, 'total_pontos' => 0, 'por_bonus' => []];

        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT id, bonus_id, pontos_creditados, exigencia_atingida, creditado_em,
                        anulado_em, anulado_por, motivo_anulacao
                   FROM evento_bonus_creditos
                  WHERE evento_inscricao_id = :inscricao'
            );
            $stmt->execute(['inscricao' => (int) $inscricaoId]);
            $linhas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao resumir a inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return $vazio;
        }

        $resumo = $vazio;

        foreach ($linhas as $linha) {
            $resumo['por_bonus'][(int) $linha['bonus_id']] = [
                'id' => (int) $linha['id'],
                'pontos' => (int) $linha['pontos_creditados'],
                'exigencia_atingida' => (int) $linha['exigencia_atingida'],
                'creditado_em' => $linha['creditado_em'],
                'anulado_em' => $linha['anulado_em'],
                // Nulo com anulado_em preenchido significa anulado PELO
                // SISTEMA (bloco E): e' o unico tipo de anulacao que volta
                // sozinha quando a condicao e' cumprida de novo.
                'anulado_por' => $linha['anulado_por'] !== null ? (int) $linha['anulado_por'] : null,
                'motivo_anulacao' => $linha['motivo_anulacao'],
            ];

            if ($linha['anulado_em'] === null) {
                $resumo['total_bonus']++;
                $resumo['total_pontos'] += (int) $linha['pontos_creditados'];
            }
        }

        return $resumo;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_bonus_creditos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Lista da tela administrativa, nominal: sem o nome nao ha' como entregar
     * a muda a quem fechou o Bingo nem como conferir um credito suspeito.
     */
    public function listarDoEvento($eventoId, array $filtros = [])
    {
        $sql =
            'SELECT c.*, b.nome AS bonus_nome, b.tipo AS bonus_tipo,
                    u.nome AS participante_nome, u.email AS participante_email,
                    a.nome AS anulado_por_nome
               FROM evento_bonus_creditos c
               INNER JOIN evento_bonus b ON b.id = c.bonus_id
               INNER JOIN evento_inscricoes i ON i.id = c.evento_inscricao_id
               INNER JOIN usuarios u ON u.id = i.usuario_id
               LEFT JOIN usuarios a ON a.id = c.anulado_por
              WHERE c.evento_id = :evento';
        $parametros = ['evento' => (int) $eventoId];

        if (!empty($filtros['bonus_id'])) {
            $sql .= ' AND c.bonus_id = :bonus_id';
            $parametros['bonus_id'] = (int) $filtros['bonus_id'];
        }

        if (isset($filtros['situacao']) && $filtros['situacao'] === 'anulados') {
            $sql .= ' AND c.anulado_em IS NOT NULL';
        } elseif (isset($filtros['situacao']) && $filtros['situacao'] === 'validos') {
            $sql .= ' AND c.anulado_em IS NULL';
        }

        $sql .= ' ORDER BY c.creditado_em DESC, c.id DESC';

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Numeros da tela administrativa: creditos validos, anulados, pontos
     * validos e quantas pessoas ja' ganharam algum bonus.
     */
    public function numerosPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN anulado_em IS NULL THEN 1 ELSE 0 END), 0) AS validos,
                    COALESCE(SUM(CASE WHEN anulado_em IS NOT NULL THEN 1 ELSE 0 END), 0) AS anulados,
                    COALESCE(SUM(CASE WHEN anulado_em IS NULL THEN pontos_creditados ELSE 0 END), 0) AS pontos,
                    COUNT(DISTINCT evento_inscricao_id) AS pessoas
               FROM evento_bonus_creditos WHERE evento_id = :evento'
        );
        $stmt->execute(['evento' => (int) $eventoId]);
        $linha = $stmt->fetch();

        if ($linha === false) {
            return ['total' => 0, 'validos' => 0, 'anulados' => 0, 'pontos' => 0, 'pessoas' => 0];
        }

        return [
            'total' => (int) $linha['total'],
            'validos' => (int) $linha['validos'],
            'anulados' => (int) $linha['anulados'],
            'pontos' => (int) $linha['pontos'],
            'pessoas' => (int) $linha['pessoas'],
        ];
    }

    /**
     * Relacao nominal de quem fechou um bonus, para a exportacao. O documento
     * do participante sai formatado por CpfValidador::formatar() no
     * controller, nunca aqui.
     */
    public function listarFechamentos($bonusId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, u.nome AS participante_nome, u.email AS participante_email,
                    up.documento AS participante_documento, up.tipo_documento AS participante_tipo_documento
               FROM evento_bonus_creditos c
               INNER JOIN evento_inscricoes i ON i.id = c.evento_inscricao_id
               INNER JOIN usuarios u ON u.id = i.usuario_id
               LEFT JOIN usuarios_perfil up ON up.usuario_id = u.id
              WHERE c.bonus_id = :bonus
              ORDER BY u.nome ASC'
        );
        $stmt->execute(['bonus' => (int) $bonusId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 57 (bloco E): anulacao PELO SISTEMA, quando o credito perde a base
     * porque uma presenca foi removida pelo Administrador.
     *
     * anulado_por fica NULO, e e' isso que a distingue da anulacao feita por
     * pessoa: esta aqui volta sozinha assim que a condicao for cumprida de
     * novo, pela propria apuracao, enquanto a humana so' volta pela reversao
     * humana.
     */
    public function anularPeloSistema($id, $motivo)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_bonus_creditos
                SET anulado_em = NOW(), anulado_por = NULL, motivo_anulacao = :motivo
              WHERE id = :id AND anulado_em IS NULL'
        );
        $stmt->execute(['motivo' => $motivo, 'id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar(
                'anular_credito_bonus_pelo_sistema',
                'evento_bonus_creditos',
                (int) $id,
                $antes !== null ? ['anulado_em' => null] : null,
                ['motivo_anulacao' => $motivo]
            );
        }

        return $alterou;
    }

    /**
     * Desfaz SO' a anulacao feita pelo sistema (anulado_por nulo). Anulacao
     * humana nunca e' desfeita por aqui: para ela existe reverterAnulacao(),
     * que exige a acao de um Administrador.
     */
    public function reverterAnulacaoAutomatica($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_bonus_creditos
                SET anulado_em = NULL, motivo_anulacao = NULL
              WHERE id = :id AND anulado_em IS NOT NULL AND anulado_por IS NULL'
        );
        $stmt->execute(['id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar(
                'reverter_anulacao_bonus_pelo_sistema',
                'evento_bonus_creditos',
                (int) $id,
                null,
                ['motivo' => 'A condição do bônus voltou a ser cumprida.']
            );
        }

        return $alterou;
    }

    /**
     * Fase 57 (bloco E, pendencia 35): anula de uma vez todos os creditos
     * validos de um bonus. E' anulacao HUMANA (anulado_por preenchido),
     * entao nao volta sozinha.
     *
     * Devolve os creditos atingidos, para que o controller avise cada
     * participante e registre o resumo na auditoria.
     */
    public function anularEmLote($bonusId, $usuarioId, $motivo)
    {
        $atingidos = $this->listarValidosDoBonus($bonusId);

        if ($atingidos === []) {
            return [];
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_bonus_creditos
                SET anulado_em = NOW(), anulado_por = :usuario, motivo_anulacao = :motivo
              WHERE bonus_id = :bonus AND anulado_em IS NULL'
        );
        $stmt->execute(['usuario' => (int) $usuarioId, 'motivo' => $motivo, 'bonus' => (int) $bonusId]);

        Auditoria::registrar('anular_creditos_bonus_em_lote', 'evento_bonus_creditos', (int) $bonusId, null, [
            'creditos_atingidos' => count($atingidos),
            'motivo_anulacao' => $motivo,
        ]);

        return $atingidos;
    }

    /**
     * Desfaz o cancelamento em lote: reverte so' os creditos daquele bonus
     * anulados por pessoa, deixando intactos os que o sistema anulou por
     * falta de base.
     */
    public function reverterAnulacaoEmLote($bonusId, $usuarioId)
    {
        $atingidos = $this->listarAnuladosPorPessoaDoBonus($bonusId);

        if ($atingidos === []) {
            return [];
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_bonus_creditos
                SET anulado_em = NULL, anulado_por = NULL, motivo_anulacao = NULL
              WHERE bonus_id = :bonus AND anulado_em IS NOT NULL AND anulado_por IS NOT NULL'
        );
        $stmt->execute(['bonus' => (int) $bonusId]);

        Auditoria::registrar('reverter_anulacao_bonus_em_lote', 'evento_bonus_creditos', (int) $bonusId, null, [
            'creditos_atingidos' => count($atingidos),
            'revertido_por' => (int) $usuarioId,
        ]);

        return $atingidos;
    }

    public function listarValidosDoBonus($bonusId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, i.usuario_id
               FROM evento_bonus_creditos c
               INNER JOIN evento_inscricoes i ON i.id = c.evento_inscricao_id
              WHERE c.bonus_id = :bonus AND c.anulado_em IS NULL'
        );
        $stmt->execute(['bonus' => (int) $bonusId]);

        return $stmt->fetchAll();
    }

    public function listarAnuladosPorPessoaDoBonus($bonusId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, i.usuario_id
               FROM evento_bonus_creditos c
               INNER JOIN evento_inscricoes i ON i.id = c.evento_inscricao_id
              WHERE c.bonus_id = :bonus AND c.anulado_em IS NOT NULL AND c.anulado_por IS NOT NULL'
        );
        $stmt->execute(['bonus' => (int) $bonusId]);

        return $stmt->fetchAll();
    }

    public function anular($id, $usuarioId, $motivo)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_bonus_creditos
                SET anulado_em = NOW(), anulado_por = :usuario, motivo_anulacao = :motivo
              WHERE id = :id AND anulado_em IS NULL'
        );
        $stmt->execute([
            'usuario' => (int) $usuarioId,
            'motivo' => $motivo,
            'id' => (int) $id,
        ]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar(
                'anular_credito_bonus',
                'evento_bonus_creditos',
                (int) $id,
                $antes !== null ? ['anulado_em' => null] : null,
                ['anulado_por' => (int) $usuarioId, 'motivo_anulacao' => $motivo]
            );
        }

        return $alterou;
    }

    public function reverterAnulacao($id, $usuarioId)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_bonus_creditos
                SET anulado_em = NULL, anulado_por = NULL, motivo_anulacao = NULL
              WHERE id = :id AND anulado_em IS NOT NULL'
        );
        $stmt->execute(['id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar(
                'reverter_anulacao_bonus',
                'evento_bonus_creditos',
                (int) $id,
                $antes !== null ? ['motivo_anulacao' => $antes['motivo_anulacao']] : null,
                ['revertido_por' => (int) $usuarioId]
            );
        }

        return $alterou;
    }
}
