<?php

namespace App\Middleware;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;

class RoleMiddleware
{
    public static function exigir(array $perfis, $concursoId = null)
    {
        if (!Auth::autenticado()) {
            header('Location: ' . url('auth/login'));
            exit;
        }

        foreach ($perfis as $perfil) {
            if (Auth::temPerfil($perfil, $concursoId)) {
                return;
            }
        }

        http_response_code(403);
        exit('Acesso negado: perfil insuficiente para esta acao.');
    }
}
