<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class Auth
{
    public static function autenticado()
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function usuarioId()
    {
        return isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    }

    public static function nome()
    {
        return isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;
    }

    public static function perfis()
    {
        return isset($_SESSION['perfis']) ? $_SESSION['perfis'] : [];
    }

    public static function temPerfil($perfil, $concursoId = null)
    {
        foreach (self::perfis() as $vinculo) {
            if ($vinculo['perfil'] !== $perfil) {
                continue;
            }

            if ($vinculo['concurso_id'] === null) {
                return true;
            }

            if ($concursoId !== null && (int) $vinculo['concurso_id'] === (int) $concursoId) {
                return true;
            }
        }

        return false;
    }

    public static function login(array $usuario, array $perfis)
    {
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['perfis'] = $perfis;
    }

    public static function logout()
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
    }
}
