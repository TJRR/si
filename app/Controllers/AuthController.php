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
            $erro = $this->entrarComResultado($resultado, null);

            if ($erro === null) {
                return;
            }
        }

        $this->renderizar('auth/login', ['erro' => $erro], 'Entrar');
    }

    /**
     * Fase 48B: porta de entrada propria do app de Evento, com identidade
     * visual distinta da porta do Concurso (login()) - o formulario e' o
     * mesmo (auth/login com $contextoEvento), mas o destino pos-login e'
     * sempre eventoApp/index, mesmo para quem tambem e' Administrador ou
     * participante do Concurso (ver entrarComResultado()).
     */
    public function loginEvento($id = null)
    {
        $erro = null;
        $eventoIdContexto = $this->eventoDoContexto($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

            $resultado = (new AuthService())->autenticar($email, $senha);
            $erro = $this->entrarComResultado($resultado, 'evento');

            if ($erro === null) {
                return;
            }
        }

        $this->renderizar('auth/login', ['erro' => $erro, 'contextoEvento' => true, 'eventoIdContexto' => $eventoIdContexto], 'Entrar - Evento');
    }

    /**
     * Reabertura da Fase 51 (achado do teste de fumaca, item 1): evento de
     * onde a pessoa veio, para os links "Cadastre-se" e "Voltar" da porta de
     * entrada do Evento levarem ao cadastro e a pagina daquele evento, e nao
     * ao cadastro e a home do Concurso. Vem do endereco (auth/loginEvento/3)
     * ou, sem ele, do retorno guardado na sessao (eventoInscricao/index/3,
     * trabalho/formulario/3, eventoApp/index/3).
     */
    private function eventoDoContexto($id)
    {
        if ($id !== null && (int) $id > 0) {
            return (int) $id;
        }

        $retorno = isset($_SESSION['retorno_apos_login']['destino']) ? (string) $_SESSION['retorno_apos_login']['destino'] : '';

        return preg_match('#/([1-9][0-9]*)$#', $retorno, $partes) === 1 ? (int) $partes[1] : null;
    }

    /**
     * Fase 48B: passo comum a login()/loginEvento()/googleCallback() apos
     * autenticar (por senha ou Google) - login na sessao, auditoria e
     * destino. $contexto === 'evento' forca o destino para eventoApp/index,
     * ignorando a prioridade de perfil de Auth::destinoPainel() (mesmo
     * criterio de AuthService::resolverUsuarioGoogle($code, $contexto)).
     * Devolve null quando o login deu certo (a chamadora ja redirecionou),
     * ou a mensagem de erro para renderizar de novo o formulario.
     */
    private function entrarComResultado(array $resultado, $contexto)
    {
        if (!$resultado['sucesso']) {
            return $resultado['mensagem'];
        }

        Auth::login($resultado['usuario'], $resultado['perfis']);
        Auditoria::registrar('login', 'usuarios', $resultado['usuario']['id']);

        if (!$this->redirecionarPosLogin()) {
            $this->redirecionar($contexto === 'evento' ? 'eventoApp/index' : Auth::destinoPainel());
        }

        return null;
    }

    /**
     * Fase 40/41: retorno automatico para a tela de inscricao no evento (ou
     * para o shell do aplicativo) apos login/cadastro -
     * EventoInscricaoPublicaController::index() e EventoAppController::index()
     * gravam $_SESSION['retorno_apos_login'] quando um visitante nao
     * autenticado acessa uma dessas telas. Escopo restrito de proposito: so'
     * aceita os padroes exatos 'eventoInscricao/index/<numero>' e
     * 'eventoApp/index' (com '/<numero>' opcional) - nunca uma URL arbitraria
     * vinda da sessao - e expira em 30min, para nao redirecionar de volta pra
     * la' um Admin que so' passou por curiosidade horas antes.
     */
    private function redirecionarPosLogin()
    {
        if (!isset($_SESSION['retorno_apos_login'])) {
            return false;
        }

        $retorno = $_SESSION['retorno_apos_login'];
        unset($_SESSION['retorno_apos_login']);

        if (!is_array($retorno) || !isset($retorno['expira_em'], $retorno['destino']) || time() >= $retorno['expira_em']) {
            return false;
        }

        $padroesAceitos = [
            '#^eventoInscricao/index/[1-9][0-9]*$#',
            '#^eventoApp/index(/[1-9][0-9]*)?$#',
            // Fase 49: visitante sem conta que tentou submeter um Trabalho
            // volta direto ao formulario apos logar/cadastrar, em vez de
            // cair no destino padrao de Auth::destinoPainel().
            '#^trabalho/formulario/[1-9][0-9]*$#',
        ];

        foreach ($padroesAceitos as $padrao) {
            if (preg_match($padrao, $retorno['destino']) === 1) {
                $this->redirecionar($retorno['destino']);
                return true;
            }
        }

        return false;
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

        // Fase 40: contexto "evento" propagado pela ida-e-volta ao Google
        // (mesmo mecanismo do oauth_state) - usado em
        // AuthService::resolverUsuarioGoogle() pra decidir a auto-aprovacao
        // do perfil "inscrito". Whitelist estrita: qualquer outro valor e'
        // ignorado.
        if (isset($_GET['contexto']) && $_GET['contexto'] === 'evento') {
            $_SESSION['oauth_contexto'] = 'evento';
        } else {
            unset($_SESSION['oauth_contexto']);
        }

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

        $contexto = isset($_SESSION['oauth_contexto']) ? $_SESSION['oauth_contexto'] : null;
        unset($_SESSION['oauth_contexto']);

        $resultado = (new AuthService())->autenticarComGoogle($code, $contexto);
        $erro = $this->entrarComResultado($resultado, $contexto);

        if ($erro === null) {
            return;
        }

        $this->renderizar('auth/login', [
            'erro' => $erro,
            'contextoEvento' => $contexto === 'evento',
        ], 'Entrar');
    }

    /**
     * "Esqueci minha senha": formulario publico de e-mail. A resposta e'
     * sempre a mesma mensagem generica, exista ou nao o e-mail no sistema -
     * ver AuthService::solicitarRecuperacaoSenha() pro motivo (evitar que a
     * tela vire um jeito de descobrir quais e-mails tem cadastro ativo).
     */
    public function esqueciSenha()
    {
        $enviado = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');

            if ($email !== '') {
                (new AuthService())->solicitarRecuperacaoSenha($email);
            }

            $enviado = true;
        }

        $this->renderizar('auth/esqueci_senha', ['enviado' => $enviado], 'Esqueci minha senha');
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
