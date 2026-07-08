<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class EtapaRepository
{
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM etapas WHERE trilha_id = :trilha_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM etapas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $etapa = $stmt->fetch();

        return $etapa !== false ? $etapa : null;
    }

    public function buscarPorTrilhaENome($trilhaId, $nome)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM etapas WHERE trilha_id = :trilha_id AND nome = :nome LIMIT 1');
        $stmt->execute(['trilha_id' => $trilhaId, 'nome' => $nome]);

        $etapa = $stmt->fetch();

        return $etapa !== false ? $etapa : null;
    }

    public function criar(
        $trilhaId,
        $nome,
        $descricao,
        $ordem,
        $dataInicio,
        $dataFim,
        $formularioDinamicoId,
        $regraTransicaoTipo = '',
        $regraTransicaoValor = ''
    ) {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO etapas (trilha_id, nome, descricao, ordem, data_inicio, data_fim, formulario_dinamico_id,
                                  regra_transicao_tipo, regra_transicao_valor)
             VALUES (:trilha_id, :nome, :descricao, :ordem, :data_inicio, :data_fim, :formulario_dinamico_id,
                     :regra_transicao_tipo, :regra_transicao_valor)'
        );
        $stmt->execute([
            'trilha_id' => $trilhaId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'data_inicio' => $dataInicio !== '' ? $dataInicio : null,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'formulario_dinamico_id' => $formularioDinamicoId !== '' ? $formularioDinamicoId : null,
            'regra_transicao_tipo' => $regraTransicaoTipo !== '' ? $regraTransicaoTipo : null,
            'regra_transicao_valor' => $regraTransicaoValor !== '' ? $regraTransicaoValor : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function atualizar(
        $id,
        $nome,
        $descricao,
        $ordem,
        $dataInicio,
        $dataFim,
        $formularioDinamicoId,
        $regraTransicaoTipo = '',
        $regraTransicaoValor = ''
    ) {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE etapas
             SET nome = :nome, descricao = :descricao, ordem = :ordem, data_inicio = :data_inicio,
                 data_fim = :data_fim, formulario_dinamico_id = :formulario_dinamico_id,
                 regra_transicao_tipo = :regra_transicao_tipo, regra_transicao_valor = :regra_transicao_valor
             WHERE id = :id'
        );
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'data_inicio' => $dataInicio !== '' ? $dataInicio : null,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'formulario_dinamico_id' => $formularioDinamicoId !== '' ? $formularioDinamicoId : null,
            'regra_transicao_tipo' => $regraTransicaoTipo !== '' ? $regraTransicaoTipo : null,
            'regra_transicao_valor' => $regraTransicaoValor !== '' ? $regraTransicaoValor : null,
            'id' => $id,
        ]);
    }
}
