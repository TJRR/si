<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 54: quem representa cada estande. Um representante por estande e
 * uma pessoa com no maximo um estande por evento (chaves unicas da
 * migration 179); em outro evento, a mesma pessoa pode representar outro
 * estande. O evento_id do vinculo sai sempre do proprio estande lido do
 * banco, nunca de formulario.
 *
 * O perfil representante_estande e' global (concurso_id nulo) e so' serve
 * para o destino depois da entrada e para o controle de acesso do painel;
 * a autorizacao real (qual estande) e' este vinculo, conferido a cada acao.
 */
class EstandeRepresentanteRepository
{
    const PERFIL = 'representante_estande';

    /**
     * Representante atual do estande, com os dados da conta (para a tela
     * mostrar se o convite ainda esta pendente).
     */
    public function buscarPorEstande($estandeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT r.*, u.nome, u.email, u.ativo AS usuario_ativo,
                    (u.senha_hash IS NULL AND u.google_id IS NULL) AS convite_pendente
             FROM evento_estande_representantes r
             JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.estande_id = :estande_id
             LIMIT 1'
        );
        $stmt->execute(['estande_id' => $estandeId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * O estande, se (e so' se) a pessoa o representa.
     */
    public function buscarVinculo($estandeId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT e.*
             FROM evento_estande_representantes r
             JOIN evento_estandes e ON e.id = r.estande_id
             WHERE r.estande_id = :estande_id AND r.usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->execute(['estande_id' => $estandeId, 'usuario_id' => $usuarioId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Estande que a pessoa representa num evento, se houver.
     */
    public function buscarPorEventoEUsuario($eventoId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT e.*
             FROM evento_estande_representantes r
             JOIN evento_estandes e ON e.id = r.estande_id
             WHERE r.evento_id = :evento_id AND r.usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->execute(['evento_id' => $eventoId, 'usuario_id' => $usuarioId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Todos os estandes que a pessoa representa, em qualquer evento, com o
     * nome do evento e a contagem de visitas.
     */
    public function listarPorUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT e.*, ev.nome AS evento_nome,
                    (SELECT COUNT(*) FROM evento_estande_visitas v WHERE v.estande_id = e.id) AS total_visitas
             FROM evento_estande_representantes r
             JOIN evento_estandes e ON e.id = r.estande_id
             JOIN eventos ev ON ev.id = e.evento_id
             WHERE r.usuario_id = :usuario_id
             ORDER BY ev.data_inicio DESC, e.nome ASC'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Grava o vinculo dentro da transacao de quem chama (o servico de
     * convite), com o evento lido do proprio estande.
     */
    public function vincularNaTransacaoAtual($estandeId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT evento_id FROM evento_estandes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $estandeId]);
        $eventoId = $stmt->fetchColumn();

        if ($eventoId === false) {
            throw new \RuntimeException('Estande não encontrado.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO evento_estande_representantes (evento_id, estande_id, usuario_id)
             VALUES (:evento_id, :estande_id, :usuario_id)'
        );
        $stmt->execute(['evento_id' => (int) $eventoId, 'estande_id' => $estandeId, 'usuario_id' => $usuarioId]);
    }

    /**
     * Desfaz o vinculo dentro da transacao de quem chama e, se a pessoa nao
     * representar mais nenhum estande em nenhum evento, retira o perfil
     * representante_estande dela (so' essa linha, global). Sem isso, o
     * destino depois da entrada continuaria levando a um painel vazio.
     * Devolve o vinculo removido, ou null se nao havia.
     */
    public function desvincularNaTransacaoAtual($estandeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_estande_representantes WHERE estande_id = :estande_id FOR UPDATE');
        $stmt->execute(['estande_id' => $estandeId]);
        $vinculo = $stmt->fetch();

        if ($vinculo === false) {
            return null;
        }

        $stmt = $pdo->prepare('DELETE FROM evento_estande_representantes WHERE id = :id');
        $stmt->execute(['id' => (int) $vinculo['id']]);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_estande_representantes WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => (int) $vinculo['usuario_id']]);

        if ((int) $stmt->fetchColumn() === 0) {
            $stmt = $pdo->prepare(
                'DELETE upc FROM usuario_perfil_concurso upc
                 JOIN perfis p ON p.id = upc.perfil_id
                 WHERE upc.usuario_id = :usuario_id AND p.chave = :chave AND upc.concurso_id IS NULL'
            );
            $stmt->execute(['usuario_id' => (int) $vinculo['usuario_id'], 'chave' => self::PERFIL]);
        }

        return $vinculo;
    }

    public function desvincular($estandeId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $vinculo = $this->desvincularNaTransacaoAtual($estandeId);
            $pdo->commit();

            return $vinculo;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}
