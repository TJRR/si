<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

abstract class Controller
{
    protected function renderizar($view, array $dados = [], $titulo = null)
    {
        View::renderizar($view, $dados, $titulo);
    }

    protected function redirecionar($rota)
    {
        header('Location: ' . url($rota));
        exit;
    }
}
