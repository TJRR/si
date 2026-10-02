<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 57: catalogo de bonus de um evento. Bonus e' ENTIDADE CADASTRAVEL,
 * nao regra fixa em codigo: nome, tipo, exigencia e pontos vem da tela, e a
 * proxima edicao do evento pode ter outros bonus sem codigo novo. O que o
 * codigo sabe fazer e' apurar cada tipo (BonusApuracaoService).
 *
 * Molde de EventoCampoInscricaoRepository (ordem nascendo como MAX + 1,
 * reordenacao por arrasto em transacao), inclusive a correcao de seguranca
 * da Fase 50: o WHERE da reordenacao inclui evento_id.
 */
class BonusRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT b.*, t.nome AS tipo_atividade_nome,
                    (SELECT COUNT(*) FROM evento_bonus_creditos c
                      WHERE c.bonus_id = b.id AND c.anulado_em IS NULL) AS total_creditos
               FROM evento_bonus b
               LEFT JOIN evento_atividade_tipos t ON t.id = b.tipo_atividade_id
              WHERE b.evento_id = :evento_id
              ORDER BY b.ordem ASC, b.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Bonus ativos do evento, na ordem cadastrada. E' o que a apuracao
     * percorre e o que o painel do participante mostra. Recurso opcional da
     * tela do participante: falha de banco devolve lista vazia, entao tabela
     * ainda nao criada faz o bloco sumir em vez de derrubar o painel.
     */
    public function listarAtivos($eventoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT b.*, t.nome AS tipo_atividade_nome
                   FROM evento_bonus b
                   LEFT JOIN evento_atividade_tipos t ON t.id = b.tipo_atividade_id
                  WHERE b.evento_id = :evento_id AND b.ativo = 1
                  ORDER BY b.ordem ASC, b.id ASC'
            );
            $stmt->execute(['evento_id' => $eventoId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao listar os bonus do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return [];
        }
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT b.*, t.nome AS tipo_atividade_nome
               FROM evento_bonus b
               LEFT JOIN evento_atividade_tipos t ON t.id = b.tipo_atividade_id
              WHERE b.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Duplicata exata: mesmo tipo, mesma exigencia e mesmo tipo de atividade
     * no mesmo evento. A conferencia e' aqui, e nao numa chave unica do
     * banco, porque tipo_atividade_id e' nulo em tres dos quatro tipos e
     * linha com coluna nula nao colide em UNIQUE no MySQL: a chave
     * funcionaria num tipo e falharia nos outros tres.
     */
    public function existeDuplicata($eventoId, $tipo, $exigencia, $tipoAtividadeId, $ignorarId = null, $camposPerfil = null)
    {
        $pdo = Database::conexao();
        $sql = 'SELECT id FROM evento_bonus
                 WHERE evento_id = :evento_id AND tipo = :tipo AND exigencia = :exigencia
                   AND ' . ($tipoAtividadeId === null ? 'tipo_atividade_id IS NULL' : 'tipo_atividade_id = :tipo_atividade_id');
        $parametros = [
            'evento_id' => $eventoId,
            'tipo' => $tipo,
            'exigencia' => $exigencia,
        ];

        if ($tipoAtividadeId !== null) {
            $parametros['tipo_atividade_id'] = $tipoAtividadeId;
        }

        // Fase 58: dois bonus do tipo perfil_campos com listas diferentes
        // sao legitimos ("Completar perfil" e "Contato e minicurriculo").
        // A lista e' gravada sempre na mesma ordem, entao comparar o texto
        // basta.
        $sql .= ' AND ' . ($camposPerfil === null ? 'campos_perfil IS NULL' : 'campos_perfil = :campos_perfil');

        if ($camposPerfil !== null) {
            $parametros['campos_perfil'] = $camposPerfil;
        }

        if ($ignorarId !== null) {
            $sql .= ' AND id <> :ignorar_id';
            $parametros['ignorar_id'] = $ignorarId;
        }

        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($parametros);

        return $stmt->fetch() !== false;
    }

    /**
     * Existe credito deste bonus, anulado ou nao? E' a trava que impede
     * apagar o bonus e trocar o tipo dele: credito de uma regra nao pode
     * passar a valer como credito de outra, com o mesmo nome.
     */
    public function temCredito($bonusId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT id FROM evento_bonus_creditos WHERE bonus_id = :bonus_id LIMIT 1');
        $stmt->execute(['bonus_id' => $bonusId]);

        return $stmt->fetch() !== false;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM evento_bonus WHERE evento_id = :evento_id');
        $stmtOrdem->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmtOrdem->fetchColumn();

        $campos = [
            'evento_id' => $eventoId,
            'ordem' => $proximaOrdem,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'],
            'tipo' => $dados['tipo'],
            'exigencia' => $dados['exigencia'],
            'tipo_atividade_id' => $dados['tipo_atividade_id'],
            'pontos' => $dados['pontos'],
            'ativo' => $dados['ativo'],
            'campos_perfil' => isset($dados['campos_perfil']) ? $dados['campos_perfil'] : null,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO evento_bonus (evento_id, ordem, nome, descricao, tipo, exigencia, tipo_atividade_id, pontos, ativo, campos_perfil)
             VALUES (:evento_id, :ordem, :nome, :descricao, :tipo, :exigencia, :tipo_atividade_id, :pontos, :ativo, :campos_perfil)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_bonus', $id, null, $campos);

        return $id;
    }

    /**
     * O tipo nunca entra aqui: bonus com credito nao pode trocar de tipo, e
     * bonus sem credito tambem nao precisa (o Administrador apaga e cria
     * outro). Mudar exigencia e pontos e' permitido e nao e' retroativo: os
     * creditos ja' dados guardam os proprios numeros.
     */
    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = [
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'],
            'exigencia' => $dados['exigencia'],
            'tipo_atividade_id' => $dados['tipo_atividade_id'],
            'pontos' => $dados['pontos'],
            'ativo' => $dados['ativo'],
            // Fase 58: sem a chave, a lista de campos do perfil fica como
            // estava. A desativacao de BonusAdminController::remover() chama
            // este metodo com uma lista explicita que nao a traz, e nao pode
            // apaga-la.
            'campos_perfil' => array_key_exists('campos_perfil', $dados)
                ? $dados['campos_perfil']
                : ($antes !== null ? $antes['campos_perfil'] : null),
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_bonus
                SET nome = :nome, descricao = :descricao, exigencia = :exigencia,
                    tipo_atividade_id = :tipo_atividade_id, pontos = :pontos, ativo = :ativo,
                    campos_perfil = :campos_perfil
              WHERE id = :id'
        );
        $stmt->execute($campos + ['id' => $id]);

        Auditoria::registrar('atualizar', 'evento_bonus', $id, $antes, $campos);
    }

    /**
     * Remocao de verdade so' em bonus que nunca creditou ninguem; o resto e'
     * desativado por atualizar(). Quem decide qual dos dois caminhos seguir
     * e' o controller, com temCredito().
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_bonus WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'evento_bonus', $id, $antes, null);
    }

    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_bonus SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_bonus', null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Quantas atividades do evento estao sem tipo cadastrado. A tela de
     * cadastro do tipo "atividades_do_tipo" avisa com este numero: atividade
     * sem tipo nunca entra nesse bonus.
     */
    public function atividadesSemTipo($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_atividades WHERE evento_id = :evento_id AND tipo_id IS NULL');
        $stmt->execute(['evento_id' => $eventoId]);

        return (int) $stmt->fetchColumn();
    }
}
