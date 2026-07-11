<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

abstract class Controller
{
    protected function renderizar($view, array $dados = [], $titulo = null, array $noAtual = null)
    {
        if (requisicaoParcial()) {
            View::renderizarParcial($view, $dados, $titulo, $noAtual);
            return;
        }

        View::renderizar($view, $dados, $titulo, $noAtual);
    }

    protected function redirecionar($rota)
    {
        header('Location: ' . url($rota));
        exit;
    }
}
