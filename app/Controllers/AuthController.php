<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\GoogleOAuth;
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
                $this->redirecionar('home/administrativo');
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

    public function google()
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        header('Location: ' . GoogleOAuth::urlAutorizacao($state));
        exit;
    }

    public function googleCallback()
    {
        if (isset($_GET['error'])) {
            unset($_SESSION['oauth_state']);
            $this->renderizar('auth/login', ['erro' => 'Login com Google cancelado.'], 'Entrar');
            return;
        }

        $stateSessao = isset($_SESSION['oauth_state']) ? $_SESSION['oauth_state'] : null;
        unset($_SESSION['oauth_state']);

        $stateRecebido = isset($_GET['state']) ? $_GET['state'] : null;

        if ($stateSessao === null || $stateRecebido === null || !hash_equals($stateSessao, $stateRecebido)) {
            $this->renderizar('auth/login', ['erro' => 'Sessao de login invalida ou expirada. Tente novamente.'], 'Entrar');
            return;
        }

        $code = isset($_GET['code']) ? $_GET['code'] : null;

        if ($code === null) {
            $this->renderizar('auth/login', ['erro' => 'Nao foi possivel completar o login com Google.'], 'Entrar');
            return;
        }

        $resultado = (new AuthService())->autenticarComGoogle($code);

        if ($resultado['sucesso']) {
            Auth::login($resultado['usuario'], $resultado['perfis']);
            $this->redirecionar('home/administrativo');
            return;
        }

        $this->renderizar('auth/login', ['erro' => $resultado['mensagem']], 'Entrar');
    }
}
