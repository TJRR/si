<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class SubmissaoRepository
{
    public function criar($etapaId, $formularioDinamicoId, array $dadosJson)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO submissoes (etapa_id, formulario_dinamico_id, dados_json)
             VALUES (:etapa_id, :formulario_dinamico_id, :dados_json)'
        );
        $stmt->execute([
            'etapa_id' => $etapaId,
            'formulario_dinamico_id' => $formularioDinamicoId,
            'dados_json' => json_encode($dadosJson),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function buscarPorEquipe($equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM submissoes WHERE equipe_id = :equipe_id LIMIT 1');
        $stmt->execute(['equipe_id' => $equipeId]);

        $submissao = $stmt->fetch();

        return $submissao !== false ? $submissao : null;
    }

    public function vincularEquipe($id, $equipeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE submissoes SET equipe_id = :equipe_id WHERE id = :id');
        $stmt->execute(['equipe_id' => $equipeId, 'id' => $id]);
    }

    public function atualizarDadosJson($id, array $dadosJson)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE submissoes SET dados_json = :dados_json WHERE id = :id');
        $stmt->execute([
            'dados_json' => json_encode($dadosJson),
            'id' => $id,
        ]);
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM submissoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $submissao = $stmt->fetch();

        return $submissao !== false ? $submissao : null;
    }

    public function cpfJaExisteNaTrilha($trilhaId, $cpf)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM submissao_cpfs WHERE trilha_id = :trilha_id AND cpf = :cpf'
        );
        $stmt->execute(['trilha_id' => $trilhaId, 'cpf' => $cpf]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function inserirCpf($submissaoId, $trilhaId, $cpf)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO submissao_cpfs (submissao_id, trilha_id, cpf) VALUES (:submissao_id, :trilha_id, :cpf)'
        );
        $stmt->execute([
            'submissao_id' => $submissaoId,
            'trilha_id' => $trilhaId,
            'cpf' => $cpf,
        ]);
    }
}
