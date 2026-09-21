<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 39 (correcao pos-teste): campos configuraveis do formulario de
 * inscricao de um evento - "Documento" e' estrutural e nunca passa por
 * aqui (sempre presente, fora da configuracao do Admin). Tabela paralela
 * a campos_dinamicos (motor do concurso), ver comentario da migration 121
 * para o porque de nao reaproveitar aquela tabela.
 */
class EventoCampoInscricaoRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_campos_inscricao WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_campos_inscricao WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $campo = $stmt->fetch();

        return $campo !== false ? $campo : null;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM evento_campos_inscricao WHERE evento_id = :evento_id');
        $stmtOrdem->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmtOrdem->fetchColumn();

        $campos = [
            'evento_id' => $eventoId,
            'ordem' => $proximaOrdem,
            'rotulo' => $dados['rotulo'],
            'tipo' => $dados['tipo'],
            'obrigatorio' => $dados['obrigatorio'],
            'texto_ajuda' => $dados['texto_ajuda'],
            'config_json' => $dados['config'] !== null ? json_encode($dados['config']) : null,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO evento_campos_inscricao (evento_id, ordem, rotulo, tipo, obrigatorio, texto_ajuda, config_json)
             VALUES (:evento_id, :ordem, :rotulo, :tipo, :obrigatorio, :texto_ajuda, :config_json)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_campos_inscricao', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = [
            'rotulo' => $dados['rotulo'],
            'tipo' => $dados['tipo'],
            'obrigatorio' => $dados['obrigatorio'],
            'texto_ajuda' => $dados['texto_ajuda'],
            'config_json' => $dados['config'] !== null ? json_encode($dados['config']) : null,
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_campos_inscricao
                SET rotulo = :rotulo, tipo = :tipo, obrigatorio = :obrigatorio,
                    texto_ajuda = :texto_ajuda, config_json = :config_json
              WHERE id = :id'
        );
        $stmt->execute($campos + ['id' => $id]);

        Auditoria::registrar('atualizar', 'evento_campos_inscricao', $id, $antes, $campos);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_campos_inscricao WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'evento_campos_inscricao', $id, $antes, null);
    }

    /**
     * Troca a ordem com o vizinho (cima/baixo) dentro do mesmo evento,
     * mesmo padrao de CampoDinamicoRepository::mover().
     */
    public function mover($id, $direcao)
    {
        $campo = $this->buscarPorId($id);

        if ($campo === null) {
            return;
        }

        $pdo = Database::conexao();
        $comparador = $direcao === 'cima' ? '<' : '>';
        $ordenacao = $direcao === 'cima' ? 'DESC' : 'ASC';

        $stmt = $pdo->prepare(
            "SELECT * FROM evento_campos_inscricao
              WHERE evento_id = :evento_id AND ordem $comparador :ordem
              ORDER BY ordem $ordenacao LIMIT 1"
        );
        $stmt->execute(['evento_id' => $campo['evento_id'], 'ordem' => $campo['ordem']]);
        $vizinho = $stmt->fetch();

        if ($vizinho === false) {
            return;
        }

        $pdo->beginTransaction();

        try {
            $atualizar = $pdo->prepare('UPDATE evento_campos_inscricao SET ordem = :ordem WHERE id = :id');
            $atualizar->execute(['ordem' => $vizinho['ordem'], 'id' => $campo['id']]);
            $atualizar->execute(['ordem' => $campo['ordem'], 'id' => $vizinho['id']]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
