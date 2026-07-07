<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Controllers\AuthController;
use App\Controllers\CadastroController;
use App\Controllers\HomeController;
use App\Controllers\UsuarioAdminController;

class Router
{
    private static $rotas = [
        'auth' => AuthController::class,
        'cadastro' => CadastroController::class,
        'usuarios' => UsuarioAdminController::class,
        'home' => HomeController::class,
    ];

    public function despachar($r)
    {
        $partes = explode('/', trim($r, '/'));
        $modulo = $partes[0] !== '' ? $partes[0] : 'home';
        $acao = isset($partes[1]) && $partes[1] !== '' ? $partes[1] : 'index';
        $parametros = array_slice($partes, 2);

        if (!isset(self::$rotas[$modulo])) {
            $this->naoEncontrado();
            return;
        }

        $classe = self::$rotas[$modulo];
        $controller = new $classe();

        if (!is_callable([$controller, $acao])) {
            $this->naoEncontrado();
            return;
        }

        call_user_func_array([$controller, $acao], $parametros);
    }

    private function naoEncontrado()
    {
        http_response_code(404);
        echo 'Pagina nao encontrada.';
    }
}
