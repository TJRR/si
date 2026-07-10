<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\GoogleOAuth;
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
            return ['sucesso' => false, 'mensagem' => 'E-mail ou senha inválidos.'];
        }

        if (!password_verify($senha, $usuario['senha_hash'])) {
            return ['sucesso' => false, 'mensagem' => 'E-mail ou senha inválidos.'];
        }

        if ($usuario['status'] === 'pendente') {
            return ['sucesso' => false, 'mensagem' => 'Cadastro aguardando aprovação do Administrador.'];
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
            return ['sucesso' => false, 'mensagem' => 'Já existe um cadastro com este e-mail.'];
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $id = $this->usuarios->criar($nome, $email, $hash);

        return ['sucesso' => true, 'usuario_id' => $id];
    }

    public function autenticarComGoogle($code)
    {
        $token = GoogleOAuth::trocarCodigoPorToken($code);

        if ($token === null || !isset($token['access_token'])) {
            return ['sucesso' => false, 'mensagem' => 'Não foi possível autenticar com o Google. Tente novamente.'];
        }

        $perfil = GoogleOAuth::buscarPerfil($token['access_token']);

        if ($perfil === null || !isset($perfil['sub']) || !isset($perfil['email'])) {
            return ['sucesso' => false, 'mensagem' => 'Não foi possível obter os dados da sua conta Google.'];
        }

        return $this->resolverUsuarioGoogle([
            'google_id' => $perfil['sub'],
            'email' => $perfil['email'],
            'nome' => isset($perfil['name']) ? $perfil['name'] : $perfil['email'],
            'email_verified' => isset($perfil['email_verified']) && $perfil['email_verified'] === true,
        ]);
    }

    public function resolverUsuarioGoogle(array $dadosGoogle)
    {
        if (empty($dadosGoogle['email_verified'])) {
            return ['sucesso' => false, 'mensagem' => 'O e-mail da sua conta Google não está verificado.'];
        }

        $usuario = $this->usuarios->buscarPorGoogleId($dadosGoogle['google_id']);

        if ($usuario === null) {
            $usuario = $this->usuarios->buscarPorEmail($dadosGoogle['email']);

            if ($usuario === null) {
                $id = $this->usuarios->criarComGoogle($dadosGoogle['nome'], $dadosGoogle['email'], $dadosGoogle['google_id']);
                $usuario = [
                    'id' => $id,
                    'nome' => $dadosGoogle['nome'],
                    'email' => $dadosGoogle['email'],
                    'google_id' => $dadosGoogle['google_id'],
                    'status' => 'pendente',
                ];
            } elseif ($usuario['google_id'] === null) {
                try {
                    $this->usuarios->vincularGoogleId($usuario['id'], $dadosGoogle['google_id']);
                } catch (\PDOException $e) {
                    return ['sucesso' => false, 'mensagem' => 'Não foi possível vincular sua conta Google. Entre em contato com o suporte.'];
                }

                $usuario['google_id'] = $dadosGoogle['google_id'];
            } else {
                return ['sucesso' => false, 'mensagem' => 'Este e-mail já está vinculado a outra conta Google.'];
            }
        }

        if ($usuario['status'] === 'pendente') {
            return ['sucesso' => false, 'mensagem' => 'Cadastro aguardando aprovação do Administrador.'];
        }

        if ($usuario['status'] === 'rejeitado') {
            return ['sucesso' => false, 'mensagem' => 'Cadastro rejeitado. Entre em contato com o NPI.'];
        }

        return [
            'sucesso' => true,
            'usuario' => $usuario,
            'perfis' => $this->usuarios->perfisDoUsuario($usuario['id']),
        ];
    }
}
