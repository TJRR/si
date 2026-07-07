<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Services\AuthService;

class CadastroController extends Controller
{
    public function index()
    {
        $erro = null;
        $sucesso = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

            if ($nome === '' || $email === '' || $senha === '') {
                $erro = 'Preencha nome, e-mail e senha.';
            } else {
                $resultado = (new AuthService())->cadastrar($nome, $email, $senha);

                if ($resultado['sucesso']) {
                    $sucesso = 'Cadastro realizado. Aguarde a aprovacao do Administrador para acessar o sistema.';
                } else {
                    $erro = $resultado['mensagem'];
                }
            }
        }

        $this->renderizar('auth/cadastro', ['erro' => $erro, 'sucesso' => $sucesso], 'Cadastro');
    }
}
