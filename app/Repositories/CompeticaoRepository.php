<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Services\CodigoUnicoService;

/**
 * Fase 58: competicoes e experiencias do evento (migration 190). A
 * participacao pontua pela leitura de um codigo "na mao do responsavel"
 * (dinamica de pontos v2); vencer nao pontua, entao nao ha' resultado.
 */
class CompeticaoRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, a.nome AS atividade_nome, a.data_inicio AS atividade_inicio, a.data_fim AS atividade_fim,
                    (SELECT COUNT(*) FROM evento_competicao_participacoes p
                      WHERE p.competicao_id = c.id AND p.anulado_em IS NULL) AS total_participacoes
               FROM evento_competicoes c
               LEFT JOIN evento_atividades a ON a.id = c.atividade_id
              WHERE c.evento_id = :evento
              ORDER BY c.ordem ASC, c.id ASC'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * So' as ativas, para as telas do participante. Falha de banco devolve
     * lista vazia: recurso opcional da tela nunca derruba o aplicativo.
     */
    public function listarAtivasDoEvento($eventoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT c.*, a.nome AS atividade_nome, a.data_inicio AS atividade_inicio, a.data_fim AS atividade_fim
                   FROM evento_competicoes c
                   LEFT JOIN evento_atividades a ON a.id = c.atividade_id
                  WHERE c.evento_id = :evento AND c.ativo = 1
                  ORDER BY c.ordem ASC, c.id ASC'
            );
            $stmt->execute(['evento' => (int) $eventoId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Competicoes] Falha ao listar as competicoes do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return [];
        }
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, a.nome AS atividade_nome
               FROM evento_competicoes c
               LEFT JOIN evento_atividades a ON a.id = c.atividade_id
              WHERE c.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => (int) $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Competicao dona do codigo lido, restrita ao evento do leitor, com a
     * janela da atividade ligada (quando houver).
     */
    public function buscarPorCodigo($eventoId, $codigo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.*, a.data_inicio AS atividade_inicio, a.data_fim AS atividade_fim,
                    a.antecedencia_abertura_presenca AS atividade_antecedencia
               FROM evento_competicoes c
               LEFT JOIN evento_atividades a ON a.id = c.atividade_id
              WHERE c.evento_id = :evento AND c.codigo_participacao = :codigo LIMIT 1'
        );
        $stmt->execute(['evento' => (int) $eventoId, 'codigo' => $codigo]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Competicoes ativas ligadas a atividades que a pessoa facilita (tela
     * "Minhas facilitacoes"). So' designacao ativa conta.
     */
    public function listarDoFacilitadorNoEvento($usuarioId, $eventoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT c.*, a.nome AS atividade_nome
                   FROM evento_competicoes c
                   INNER JOIN evento_atividades a ON a.id = c.atividade_id
                   INNER JOIN evento_atividade_facilitadores f
                           ON f.atividade_id = a.id AND f.usuario_id = :usuario AND f.removido_em IS NULL
                  WHERE c.evento_id = :evento AND c.ativo = 1
                  ORDER BY c.ordem ASC, c.id ASC'
            );
            $stmt->execute(['usuario' => (int) $usuarioId, 'evento' => (int) $eventoId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Competicoes] Falha ao listar as competicoes do facilitador ' . (int) $usuarioId . ': ' . $e->getMessage());

            return [];
        }
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM evento_competicoes WHERE evento_id = :evento');
        $stmt->execute(['evento' => (int) $eventoId]);
        $proximaOrdem = (int) $stmt->fetchColumn();

        $campos = [
            'evento_id' => (int) $eventoId,
            'nome' => $dados['nome'],
            'regras_html' => $dados['regras_html'] !== '' ? $dados['regras_html'] : null,
            'atividade_id' => $dados['atividade_id'],
            'codigo_participacao' => CodigoUnicoService::gerarCodigoFixoDoEvento(),
            'pontos_participacao' => (int) $dados['pontos_participacao'],
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'ordem' => $proximaOrdem,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO evento_competicoes
                 (evento_id, nome, regras_html, atividade_id, codigo_participacao, pontos_participacao, ativo, ordem)
             VALUES (:evento_id, :nome, :regras_html, :atividade_id, :codigo_participacao, :pontos_participacao, :ativo, :ordem)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_competicoes', $id, null, $campos);

        return $id;
    }

    /**
     * O codigo nunca muda: ele ja' pode estar impresso no cartao do
     * responsavel. Os pontos novos valem so' daqui para frente.
     */
    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = [
            'id' => (int) $id,
            'nome' => $dados['nome'],
            'regras_html' => $dados['regras_html'] !== '' ? $dados['regras_html'] : null,
            'atividade_id' => $dados['atividade_id'],
            'pontos_participacao' => (int) $dados['pontos_participacao'],
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_competicoes
                SET nome = :nome, regras_html = :regras_html, atividade_id = :atividade_id,
                    pontos_participacao = :pontos_participacao, ativo = :ativo
              WHERE id = :id'
        );
        $stmt->execute($campos);

        Auditoria::registrar('atualizar', 'evento_competicoes', (int) $id, $antes, $campos);
    }

    public function desativar($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE evento_competicoes SET ativo = 0 WHERE id = :id');
        $stmt->execute(['id' => (int) $id]);

        Auditoria::registrar('desativar', 'evento_competicoes', (int) $id, null, ['ativo' => 0]);
    }

    /**
     * Competicao sem nenhuma participacao pode ser apagada de verdade; com
     * participacao, a chave estrangeira de evento_competicao_participacoes
     * impede, e o controller desativa em vez de apagar.
     */
    public function temParticipacao($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_competicao_participacoes WHERE competicao_id = :id');
        $stmt->execute(['id' => (int) $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_competicoes WHERE id = :id');
        $stmt->execute(['id' => (int) $id]);

        Auditoria::registrar('remover', 'evento_competicoes', (int) $id, $antes, null);
    }

    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_competicoes SET ordem = :ordem WHERE id = :id AND evento_id = :evento');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento' => (int) $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_competicoes', null, null, ['evento_id' => (int) $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
