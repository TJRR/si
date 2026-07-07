<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class View
{
    public static function renderizar($view, array $dados = [], $titulo = null)
    {
        extract($dados);

        $caminhoView = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($caminhoView)) {
            http_response_code(500);
            exit('View nao encontrada: ' . $view);
        }

        ob_start();
        require $caminhoView;
        $conteudo = ob_get_clean();

        require __DIR__ . '/../Views/layout.php';
    }
}
