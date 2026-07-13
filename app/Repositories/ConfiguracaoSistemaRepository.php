<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ConfiguracaoSistemaRepository
{
    public function buscar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM configuracoes_sistema WHERE id = 1')->fetch();
    }

    public function atualizarSessaoTimeoutMinutos($minutos)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_sistema SET sessao_timeout_minutos = :minutos WHERE id = 1');
        $stmt->execute(['minutos' => $minutos]);

        Auditoria::registrar('atualizar', 'configuracoes_sistema', 1, $antes, ['sessao_timeout_minutos' => $minutos]);
    }
}
