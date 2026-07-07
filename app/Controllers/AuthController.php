<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function login()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

            $resultado = (new AuthService())->autenticar($email, $senha);

            if ($resultado['sucesso']) {
                Auth::login($resultado['usuario'], $resultado['perfis']);
                $this->redirecionar('home/index');
                return;
            }

            $erro = $resultado['mensagem'];
        }

        $this->renderizar('auth/login', ['erro' => $erro], 'Entrar');
    }

    public function logout()
    {
        Auth::logout();
        $this->redirecionar('auth/login');
    }
}
