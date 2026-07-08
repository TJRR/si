<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class ImportacaoPendenciaRepository
{
    public function criar($trilhaId, $equipeId, $participanteId, $tipo, $aba, $linhaPlanilha, $descricao, array $dadosBrutos = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO importacao_pendencias
                (trilha_id, equipe_id, participante_id, tipo, aba, linha_planilha, descricao, dados_brutos_json)
             VALUES
                (:trilha_id, :equipe_id, :participante_id, :tipo, :aba, :linha_planilha, :descricao, :dados_brutos_json)'
        );
        $stmt->execute([
            'trilha_id' => $trilhaId,
            'equipe_id' => $equipeId,
            'participante_id' => $participanteId,
            'tipo' => $tipo,
            'aba' => $aba,
            'linha_planilha' => $linhaPlanilha,
            'descricao' => $descricao,
            'dados_brutos_json' => $dadosBrutos !== null ? json_encode($dadosBrutos) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function listarPendentes()
    {
        $pdo = Database::conexao();

        return $pdo->query(
            "SELECT ip.*, e.nome_equipe, t.nome AS trilha_nome, p.nome AS participante_nome, p.cpf AS participante_cpf
             FROM importacao_pendencias ip
             LEFT JOIN equipes e ON e.id = ip.equipe_id
             LEFT JOIN trilhas t ON t.id = ip.trilha_id
             LEFT JOIN participantes p ON p.id = ip.participante_id
             WHERE ip.status = 'pendente'
             ORDER BY ip.tipo ASC, ip.id ASC"
        )->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM importacao_pendencias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $pendencia = $stmt->fetch();

        return $pendencia !== false ? $pendencia : null;
    }

    public function marcarResolvida($id, $usuarioId, $observacao)
    {
        $this->atualizarStatus($id, 'resolvido', $usuarioId, $observacao);
    }

    public function marcarIgnorada($id, $usuarioId, $observacao)
    {
        $this->atualizarStatus($id, 'ignorado', $usuarioId, $observacao);
    }

    private function atualizarStatus($id, $status, $usuarioId, $observacao)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE importacao_pendencias
             SET status = :status, resolvido_por = :resolvido_por, resolvido_em = NOW(), observacao_resolucao = :observacao
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'resolvido_por' => $usuarioId,
            'observacao' => $observacao !== '' ? $observacao : null,
            'id' => $id,
        ]);
    }
}
