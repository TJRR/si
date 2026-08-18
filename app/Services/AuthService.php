<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\GoogleOAuth;
use App\Repositories\TentativaLoginRepository;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\UsuarioRepository;

class AuthService
{
    private $usuarios;
    private $tokens;
    private $tentativas;

    const MENSAGEM_LOGIN_INVALIDO = 'E-mail ou senha inválidos, ou cadastro ainda não liberado.';
    const LIMITE_TENTATIVAS = 5;
    const JANELA_TENTATIVAS_MINUTOS = 15;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->tokens = new TokenSenhaRepository();
        $this->tentativas = new TentativaLoginRepository();
    }

    /**
     * Fase 31 (Auditoria de Seguranca): achado #11 (rate limiting) e achado
     * #12 (mensagem genericizada) resolvidos juntos aqui - a MESMA mensagem
     * cobre e-mail inexistente, senha errada e status pendente/rejeitado/
     * suspenso, pra nao dar pista de qual dos casos e' o real. So' credencial
     * errada conta pra rate limiting (status bloqueado com senha certa nao
     * conta - quem digitou a senha certa ja provou que sabe a credencial,
     * nao e' tentativa de adivinhar).
     */
    public function autenticar($email, $senha)
    {
        if ($this->tentativas->contarFalhasRecentes($email, self::JANELA_TENTATIVAS_MINUTOS) >= self::LIMITE_TENTATIVAS) {
            return ['sucesso' => false, 'mensagem' => 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.'];
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null || $usuario['senha_hash'] === null || !password_verify($senha, $usuario['senha_hash'])) {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
            $this->tentativas->registrarFalha($email, $ip);

            return ['sucesso' => false, 'mensagem' => self::MENSAGEM_LOGIN_INVALIDO];
        }

        if ($usuario['status'] !== 'aprovado' || (int) $usuario['ativo'] === 0) {
            return ['sucesso' => false, 'mensagem' => self::MENSAGEM_LOGIN_INVALIDO];
        }

        $this->tentativas->limparFalhas($email);

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

    /**
     * "Esqueci minha senha" - sempre silenciosa pra quem chama: nunca revela
     * se o e-mail existe, esta ativo ou aprovado (evita enumeracao de
     * contas). So' gera token/envia e-mail quando a conta realmente existe e
     * pode logar (mesma condicao de AuthService::autenticar: aprovado e
     * ativo); caso contrario, nao faz nada e retorna do mesmo jeito.
     *
     * Reaproveita o token tipo "definir" (mesmo do convite/homologacao) e a
     * pagina auth/definirSenha ja existente - funciona tanto pra definir a
     * primeira senha quanto pra redefinir uma esquecida.
     */
    public function solicitarRecuperacaoSenha($email)
    {
        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null || $usuario['status'] !== 'aprovado' || (int) $usuario['ativo'] !== 1) {
            return;
        }

        $this->tokens->invalidarPendentes($usuario['id'], 'definir');
        $token = $this->tokens->criar($usuario['id'], 'definir');
        $link = urlAbsoluta('auth/definirSenha/' . $token);

        try {
            (new NotificacaoService())->recuperacaoSenha($usuario['email'], $usuario['nome'], $link);
        } catch (\Exception $e) {
            // Falha de notificacao nunca deve alterar a resposta generica ao usuario.
        }
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
                    'ativo' => 1,
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

        if ((int) $usuario['ativo'] === 0) {
            return ['sucesso' => false, 'mensagem' => 'Cadastro suspenso. Entre em contato com o NPI.'];
        }

        return [
            'sucesso' => true,
            'usuario' => $usuario,
            'perfis' => $this->usuarios->perfisDoUsuario($usuario['id']),
        ];
    }
}
