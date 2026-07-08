<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class FormulaPontuacaoRepository
{
    public function buscarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM formulas_pontuacao WHERE etapa_id = :etapa_id LIMIT 1');
        $stmt->execute(['etapa_id' => $etapaId]);

        $formula = $stmt->fetch();

        return $formula !== false ? $formula : null;
    }

    public function buscarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM formulas_pontuacao WHERE trilha_id = :trilha_id LIMIT 1');
        $stmt->execute(['trilha_id' => $trilhaId]);

        $formula = $stmt->fetch();

        return $formula !== false ? $formula : null;
    }

    public function salvarParaEtapa($etapaId, $expressao)
    {
        $existente = $this->buscarPorEtapa($etapaId);
        $pdo = Database::conexao();

        if ($existente === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO formulas_pontuacao (etapa_id, trilha_id, expressao) VALUES (:etapa_id, NULL, :expressao)'
            );
            $stmt->execute(['etapa_id' => $etapaId, 'expressao' => $expressao]);

            return;
        }

        $stmt = $pdo->prepare('UPDATE formulas_pontuacao SET expressao = :expressao WHERE id = :id');
        $stmt->execute(['expressao' => $expressao, 'id' => $existente['id']]);
    }

    public function salvarParaTrilha($trilhaId, $expressao)
    {
        $existente = $this->buscarPorTrilha($trilhaId);
        $pdo = Database::conexao();

        if ($existente === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO formulas_pontuacao (etapa_id, trilha_id, expressao) VALUES (NULL, :trilha_id, :expressao)'
            );
            $stmt->execute(['trilha_id' => $trilhaId, 'expressao' => $expressao]);

            return;
        }

        $stmt = $pdo->prepare('UPDATE formulas_pontuacao SET expressao = :expressao WHERE id = :id');
        $stmt->execute(['expressao' => $expressao, 'id' => $existente['id']]);
    }
}
