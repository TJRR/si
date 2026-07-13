<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ConfiguracaoVisualRepository
{
    public function buscar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM configuracoes_visuais WHERE id = 1')->fetch();
    }

    public function atualizar($corPrimariaInicio, $corPrimariaFim, $corSecundaria)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE configuracoes_visuais
             SET cor_primaria_inicio = :inicio, cor_primaria_fim = :fim, cor_secundaria = :secundaria
             WHERE id = 1'
        );
        $depois = [
            'cor_primaria_inicio' => $corPrimariaInicio,
            'cor_primaria_fim' => $corPrimariaFim,
            'cor_secundaria' => $corSecundaria,
        ];
        $stmt->execute(['inicio' => $corPrimariaInicio, 'fim' => $corPrimariaFim, 'secundaria' => $corSecundaria]);

        Auditoria::registrar('atualizar', 'configuracoes_visuais', 1, $antes, $depois);
    }

    public function atualizarFavicon($caminhoRelativo)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_visuais SET favicon_path = :favicon_path WHERE id = 1');
        $stmt->execute(['favicon_path' => $caminhoRelativo]);

        Auditoria::registrar('atualizar_favicon', 'configuracoes_visuais', 1, $antes, ['favicon_path' => $caminhoRelativo]);
    }
}
