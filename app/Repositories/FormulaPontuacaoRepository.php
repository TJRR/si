<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
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

    public function salvarParaEtapa($etapaId, $expressao, $casasDecimais)
    {
        $existente = $this->buscarPorEtapa($etapaId);
        $pdo = Database::conexao();

        if ($existente === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO formulas_pontuacao (etapa_id, trilha_id, expressao, casas_decimais) VALUES (:etapa_id, NULL, :expressao, :casas_decimais)'
            );
            $stmt->execute(['etapa_id' => $etapaId, 'expressao' => $expressao, 'casas_decimais' => $casasDecimais]);

            Auditoria::registrar('salvar_para_etapa', 'formulas_pontuacao', $etapaId, null, ['expressao' => $expressao, 'casas_decimais' => $casasDecimais]);

            return;
        }

        $stmt = $pdo->prepare('UPDATE formulas_pontuacao SET expressao = :expressao, casas_decimais = :casas_decimais WHERE id = :id');
        $stmt->execute(['expressao' => $expressao, 'casas_decimais' => $casasDecimais, 'id' => $existente['id']]);

        Auditoria::registrar(
            'salvar_para_etapa',
            'formulas_pontuacao',
            $etapaId,
            ['expressao' => $existente['expressao'], 'casas_decimais' => $existente['casas_decimais']],
            ['expressao' => $expressao, 'casas_decimais' => $casasDecimais]
        );
    }

    public function salvarParaTrilha($trilhaId, $expressao, $casasDecimais)
    {
        $existente = $this->buscarPorTrilha($trilhaId);
        $pdo = Database::conexao();

        if ($existente === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO formulas_pontuacao (etapa_id, trilha_id, expressao, casas_decimais) VALUES (NULL, :trilha_id, :expressao, :casas_decimais)'
            );
            $stmt->execute(['trilha_id' => $trilhaId, 'expressao' => $expressao, 'casas_decimais' => $casasDecimais]);

            Auditoria::registrar('salvar_para_trilha', 'formulas_pontuacao', $trilhaId, null, ['expressao' => $expressao, 'casas_decimais' => $casasDecimais]);

            return;
        }

        $stmt = $pdo->prepare('UPDATE formulas_pontuacao SET expressao = :expressao, casas_decimais = :casas_decimais WHERE id = :id');
        $stmt->execute(['expressao' => $expressao, 'casas_decimais' => $casasDecimais, 'id' => $existente['id']]);

        Auditoria::registrar(
            'salvar_para_trilha',
            'formulas_pontuacao',
            $trilhaId,
            ['expressao' => $existente['expressao'], 'casas_decimais' => $existente['casas_decimais']],
            ['expressao' => $expressao, 'casas_decimais' => $casasDecimais]
        );
    }

    /**
     * Fallback seguro: 2 casas quando ainda nao ha formula cadastrada (linha
     * nao existe) ou a migration 133 ainda nao rodou (coluna ausente do array).
     */
    public static function casasDecimais($formula)
    {
        return $formula !== null && isset($formula['casas_decimais']) ? (int) $formula['casas_decimais'] : 2;
    }
}
