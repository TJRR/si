<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

class ConfiguracaoVisualRepository
{
    public function buscar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM configuracoes_visuais WHERE id = 1')->fetch();
    }

    public function atualizar($corPrimariaInicio, $corPrimariaFim)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE configuracoes_visuais SET cor_primaria_inicio = :inicio, cor_primaria_fim = :fim WHERE id = 1'
        );
        $stmt->execute(['inicio' => $corPrimariaInicio, 'fim' => $corPrimariaFim]);
    }
}
