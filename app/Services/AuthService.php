<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\UsuarioRepository;

class AuthService
{
    private $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    public function autenticar($email, $senha)
    {
        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null || $usuario['senha_hash'] === null) {
            return ['sucesso' => false, 'mensagem' => 'E-mail ou senha invalidos.'];
        }

        if (!password_verify($senha, $usuario['senha_hash'])) {
            return ['sucesso' => false, 'mensagem' => 'E-mail ou senha invalidos.'];
        }

        if ($usuario['status'] === 'pendente') {
            return ['sucesso' => false, 'mensagem' => 'Cadastro aguardando aprovacao do Administrador.'];
        }

        if ($usuario['status'] === 'rejeitado') {
            return ['sucesso' => false, 'mensagem' => 'Cadastro rejeitado. Entre em contato com o NPI.'];
        }

        $perfis = $this->usuarios->perfisDoUsuario($usuario['id']);

        return ['sucesso' => true, 'usuario' => $usuario, 'perfis' => $perfis];
    }

    public function cadastrar($nome, $email, $senha)
    {
        if ($this->usuarios->buscarPorEmail($email) !== null) {
            return ['sucesso' => false, 'mensagem' => 'Ja existe um cadastro com este e-mail.'];
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $id = $this->usuarios->criar($nome, $email, $hash);

        return ['sucesso' => true, 'usuario_id' => $id];
    }
}
