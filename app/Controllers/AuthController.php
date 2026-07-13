<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\GoogleOAuth;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\UsuarioRepository;
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
                Auditoria::registrar('login', 'usuarios', $resultado['usuario']['id']);
                $this->redirecionar(Auth::destinoPainel());
                return;
            }

            $erro = $resultado['mensagem'];
        }

        $this->renderizar('auth/login', ['erro' => $erro], 'Entrar');
    }

    public function logout()
    {
        $usuarioId = Auth::usuarioId();
        Auditoria::registrar('logout', 'usuarios', $usuarioId, null, null, null, $usuarioId);
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
            $this->renderizar('auth/login', ['erro' => 'Sessão de login inválida ou expirada. Tente novamente.'], 'Entrar');
            return;
        }

        $code = isset($_GET['code']) ? $_GET['code'] : null;

        if ($code === null) {
            $this->renderizar('auth/login', ['erro' => 'Não foi possível completar o login com Google.'], 'Entrar');
            return;
        }

        $resultado = (new AuthService())->autenticarComGoogle($code);

        if ($resultado['sucesso']) {
            Auth::login($resultado['usuario'], $resultado['perfis']);
            Auditoria::registrar('login', 'usuarios', $resultado['usuario']['id']);
            $this->redirecionar(Auth::destinoPainel());
            return;
        }

        $this->renderizar('auth/login', ['erro' => $resultado['mensagem']], 'Entrar');
    }

    public function definirSenha($token)
    {
        $registro = (new TokenSenhaRepository())->buscarValidoPorToken($token);

        if ($registro === null) {
            $this->renderizar('auth/definir_senha', [
                'erro' => 'Este link é inválido ou já expirou. Solicite um novo.',
                'token' => null,
            ], 'Definir senha');
            return;
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
            $confirmacao = isset($_POST['confirmacao']) ? $_POST['confirmacao'] : '';

            if (strlen($senha) < 8) {
                $erro = 'A senha deve ter ao menos 8 caracteres.';
            } elseif ($senha !== $confirmacao) {
                $erro = 'As senhas não conferem.';
            } else {
                (new UsuarioRepository())->definirSenha($registro['usuario_id'], password_hash($senha, PASSWORD_DEFAULT));
                (new TokenSenhaRepository())->marcarUsado($registro['id']);

                $_SESSION['flash'] = 'Senha definida com sucesso. Faça login normalmente.';
                $this->redirecionar('auth/login');
                return;
            }
        }

        $this->renderizar('auth/definir_senha', [
            'erro' => $erro,
            'token' => $token,
        ], 'Definir senha');
    }
}
