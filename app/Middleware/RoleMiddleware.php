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

    /**
     * Igual a exigir(), mas aceita o perfil vinculado a QUALQUER concurso (nao so
     * globalmente ou a um concurso especifico). Usada em telas onde o proprio metodo
     * do controller faz a checagem fina por concurso depois — aqui so validamos que
     * o usuario tem o perfil em algum lugar antes de deixar entrar na area.
     */
    public static function exigirEmQualquerConcurso(array $perfis)
    {
        if (!Auth::autenticado()) {
            header('Location: ' . url('auth/login'));
            exit;
        }

        foreach ($perfis as $perfil) {
            if (Auth::possuiPerfil($perfil)) {
                return;
            }
        }

        http_response_code(403);
        exit('Acesso negado: perfil insuficiente para esta acao.');
    }
}
