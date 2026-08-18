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
                // Fase 31 (Auditoria de Seguranca, achado #10): mensagem de
                // sucesso e' sempre a mesma, exista ou nao o e-mail antes -
                // AuthService::cadastrar() continua recusando duplicidade
                // internamente (nunca cria conta repetida), so' nao revela
                // isso pra quem preenche o formulario (evita enumeracao de
                // contas cadastradas).
                (new AuthService())->cadastrar($nome, $email, $senha);
                $sucesso = 'Cadastro recebido. Se este e-mail ainda não tinha conta, aguarde a aprovação do Administrador para acessar o sistema.';
            }
        }

        $this->renderizar('auth/cadastro', ['erro' => $erro, 'sucesso' => $sucesso], 'Cadastro');
    }
}
