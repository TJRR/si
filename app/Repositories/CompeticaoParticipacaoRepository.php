<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 58: participacoes em competicao (migration 191), com os pontos
 * congelados no valor da competicao no instante da leitura.
 *
 * Anulacao so' por pessoa, com motivo, reversivel por pessoa, no molde de
 * DivulgacaoComprovacaoRepository (Fase 56). A chave unica vale tambem para
 * linha anulada: anular nao reabre a vaga.
 */
class CompeticaoParticipacaoRepository
{
    use OperacaoEmLote;

    /**
     * Devolve true quando gravou agora e false quando a pessoa ja' tinha
     * participacao registrada, inclusive quando outra leitura simultanea
     * chegou antes (erro 23000 da chave unica).
     */
    public function registrar($eventoId, $competicaoId, $inscricaoId, $pontos)
    {
        $pdo = Database::conexao();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_competicao_participacoes
                     (evento_id, competicao_id, evento_inscricao_id, pontos_creditados, participou_em)
                 VALUES (:evento, :competicao, :inscricao, :pontos, NOW())'
            );
            $stmt->execute([
                'evento' => (int) $eventoId,
                'competicao' => (int) $competicaoId,
                'inscricao' => (int) $inscricaoId,
                'pontos' => (int) $pontos,
            ]);
            $id = (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }

        Auditoria::registrar('registrar_participacao_competicao', 'evento_competicao_participacoes', $id, null, [
            'competicao_id' => (int) $competicaoId,
            'evento_inscricao_id' => (int) $inscricaoId,
            'pontos_creditados' => (int) $pontos,
        ]);

        return true;
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.nome AS competicao_nome, i.usuario_id
               FROM evento_competicao_participacoes p
               INNER JOIN evento_competicoes c ON c.id = p.competicao_id
               INNER JOIN evento_inscricoes i ON i.id = p.evento_inscricao_id
              WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => (int) $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Lista nominal da tela administrativa, com filtro opcional por
     * competicao e por situacao.
     */
    public function listarDoEvento($eventoId, array $filtros = [])
    {
        $sql =
            'SELECT p.*, c.nome AS competicao_nome, u.nome AS participante_nome, u.email AS participante_email,
                    a.nome AS anulado_por_nome
               FROM evento_competicao_participacoes p
               INNER JOIN evento_competicoes c ON c.id = p.competicao_id
               INNER JOIN evento_inscricoes i ON i.id = p.evento_inscricao_id
               INNER JOIN usuarios u ON u.id = i.usuario_id
               LEFT JOIN usuarios a ON a.id = p.anulado_por
              WHERE p.evento_id = :evento';
        $parametros = ['evento' => (int) $eventoId];

        if (!empty($filtros['competicao_id'])) {
            $sql .= ' AND p.competicao_id = :competicao';
            $parametros['competicao'] = (int) $filtros['competicao_id'];
        }

        if (isset($filtros['situacao']) && $filtros['situacao'] === 'anuladas') {
            $sql .= ' AND p.anulado_em IS NOT NULL';
        } elseif (isset($filtros['situacao']) && $filtros['situacao'] === 'validas') {
            $sql .= ' AND p.anulado_em IS NULL';
        }

        if (!empty($filtros['busca'])) {
            $sql .= ' AND (u.nome LIKE :busca OR u.email LIKE :busca)';
            $parametros['busca'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= ' AND DATE(p.participou_em) >= :data_inicio';
            $parametros['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= ' AND DATE(p.participou_em) <= :data_fim';
            $parametros['data_fim'] = $filtros['data_fim'];
        }

        $sql .= ' ORDER BY p.participou_em DESC, p.id DESC';

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Extrato de uma pessoa, inclusive anuladas (mostradas com o motivo).
     */
    public function listarDaInscricao($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.nome AS competicao_nome
               FROM evento_competicao_participacoes p
               INNER JOIN evento_competicoes c ON c.id = p.competicao_id
              WHERE p.evento_inscricao_id = :inscricao
              ORDER BY p.participou_em ASC, p.id ASC'
        );
        $stmt->execute(['inscricao' => (int) $inscricaoId]);

        return $stmt->fetchAll();
    }

    public function anular($id, $usuarioId, $motivo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_competicao_participacoes
                SET anulado_em = NOW(), anulado_por = :usuario, motivo_anulacao = :motivo
              WHERE id = :id AND anulado_em IS NULL'
        );
        $stmt->execute(['usuario' => (int) $usuarioId, 'motivo' => $motivo, 'id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar('anular_participacao_competicao', 'evento_competicao_participacoes', (int) $id, ['anulado_em' => null], [
                'anulado_por' => (int) $usuarioId,
                'motivo_anulacao' => $motivo,
            ]);
        }

        return $alterou;
    }

    public function reverterAnulacao($id, $usuarioId)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_competicao_participacoes
                SET anulado_em = NULL, anulado_por = NULL, motivo_anulacao = NULL
              WHERE id = :id AND anulado_em IS NOT NULL'
        );
        $stmt->execute(['id' => (int) $id]);

        $alterou = $stmt->rowCount() > 0;

        if ($alterou) {
            Auditoria::registrar(
                'reverter_anulacao_participacao_competicao',
                'evento_competicao_participacoes',
                (int) $id,
                $antes !== null ? ['motivo_anulacao' => $antes['motivo_anulacao']] : null,
                ['revertido_por' => (int) $usuarioId]
            );
        }

        return $alterou;
    }

    /**
     * Fase 58: anulacao e reversao em lote, pelo traco OperacaoEmLote.
     * Devolvem as linhas de fato alteradas, para a mensagem e para o aviso a
     * cada participante atingido.
     */
    public function anularEmLote($eventoId, array $ids, $usuarioId, $motivo)
    {
        return $this->aplicarEmLote(
            $eventoId,
            $ids,
            'anular',
            'UPDATE evento_competicao_participacoes SET anulado_em = NOW(), anulado_por = ?, motivo_anulacao = ?',
            'anulado_em IS NULL',
            [(int) $usuarioId, $motivo]
        );
    }

    public function reverterAnulacaoEmLote($eventoId, array $ids)
    {
        return $this->aplicarEmLote(
            $eventoId,
            $ids,
            'reverter_anulacao',
            'UPDATE evento_competicao_participacoes SET anulado_em = NULL, anulado_por = NULL, motivo_anulacao = NULL',
            'anulado_em IS NOT NULL',
            []
        );
    }

    private function aplicarEmLote($eventoId, array $ids, $acao, $comando, $condicao, array $valores)
    {
        $identificadores = $this->identificadoresDoLote($ids);

        if ($identificadores === []) {
            return [];
        }

        // O "antes" sai da MESMA condicao do comando, para que o retorno
        // seja exatamente o que foi alterado e o controlador saiba quem
        // avisar.
        $marcadores = implode(', ', array_fill(0, count($identificadores), '?'));
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.*, c.nome AS competicao_nome, u.nome AS participante_nome, i.usuario_id
               FROM evento_competicao_participacoes p
               INNER JOIN evento_competicoes c ON c.id = p.competicao_id
               INNER JOIN evento_inscricoes i ON i.id = p.evento_inscricao_id
               INNER JOIN usuarios u ON u.id = i.usuario_id
              WHERE p.id IN (' . $marcadores . ') AND p.evento_id = ? AND p.' . $condicao
        );
        $stmt->execute(array_merge($identificadores, [(int) $eventoId]));
        $alcancadas = $stmt->fetchAll();

        if ($alcancadas === []) {
            return [];
        }

        $alcancados = array_map(function ($linha) {
            return (int) $linha['id'];
        }, $alcancadas);

        $this->executarLote($comando, $condicao, $eventoId, $alcancados, $valores, $acao, 'evento_competicao_participacoes');

        return $alcancadas;
    }
}
