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
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Formato do e-mail e tamanho da senha conferidos no servidor; a regra da
                // senha e' a mesma de AuthController::definirSenha().
                $erro = 'Informe um e-mail válido.';
            } elseif (strlen($senha) < 8) {
                $erro = 'A senha deve ter ao menos 8 caracteres.';
            } else {
                // Mensagem de sucesso unica: ver Implantar.md, secao 13.6.
                (new AuthService())->cadastrar($nome, $email, $senha);
                $sucesso = 'Cadastro recebido. Se este e-mail ainda não tinha conta, aguarde a aprovação do Administrador para acessar o sistema.';
            }
        }

        $this->renderizar('auth/cadastro', ['erro' => $erro, 'sucesso' => $sucesso], 'Cadastro');
    }
}
