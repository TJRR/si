<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class Auth
{
    public static function autenticado()
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function usuarioId()
    {
        return isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    }

    public static function nome()
    {
        return isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;
    }

    public static function perfis()
    {
        return isset($_SESSION['perfis']) ? $_SESSION['perfis'] : [];
    }

    public static function temPerfil($perfil, $concursoId = null)
    {
        foreach (self::perfis() as $vinculo) {
            if ($vinculo['perfil'] !== $perfil) {
                continue;
            }

            if ($vinculo['concurso_id'] === null) {
                return true;
            }

            if ($concursoId !== null && (int) $vinculo['concurso_id'] === (int) $concursoId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuario possui o perfil em QUALQUER concurso (ou globalmente),
     * sem exigir um concurso especifico. Usada para decisoes de navegacao (ex.: para
     * onde mandar o usuario apos o login) — a autorizacao fina por concurso continua
     * sendo feita por temPerfil() dentro de cada tela.
     */
    public static function possuiPerfil($perfil)
    {
        foreach (self::perfis() as $vinculo) {
            if ($vinculo['perfil'] === $perfil) {
                return true;
            }
        }

        return false;
    }

    /**
     * Home/painel de cada perfil - usada tanto no redirecionamento pos-login
     * quanto no link "Voltar ao painel" de telas comuns a todos os perfis
     * (ex.: Meu perfil).
     */
    public static function destinoPainel()
    {
        if (self::possuiPerfil('administrador') || self::possuiPerfil('suporte')) {
            return 'home/administrativo';
        }

        if (self::possuiPerfil('avaliador')) {
            return 'avaliacao/index';
        }

        if (self::possuiPerfil('participante')) {
            return 'participante/index';
        }

        return 'home/administrativo';
    }

    public static function login(array $usuario, array $perfis)
    {
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['perfis'] = $perfis;
    }

    /**
     * Fase 17 (Melhoria 2): Admin visualiza o sistema como outro usuario,
     * somente leitura (o bloqueio de escrita fica em Router::despachar()).
     * Guarda a identidade real do Admin em 'visualizando_de' e sobrescreve a
     * sessao com a do alvo - nao e' um novo login (mesmo navegador/pessoa),
     * por isso nao regenera o id de sessao.
     */
    public static function iniciarVisualizacaoComo($usuarioAlvoId, $nomeAlvo, array $perfisAlvo)
    {
        $_SESSION['visualizando_de'] = [
            'usuario_id' => self::usuarioId(),
            'usuario_nome' => self::nome(),
            'perfis' => self::perfis(),
        ];

        $_SESSION['usuario_id'] = $usuarioAlvoId;
        $_SESSION['usuario_nome'] = $nomeAlvo;
        $_SESSION['perfis'] = $perfisAlvo;
    }

    public static function estaVisualizandoComoOutro()
    {
        return isset($_SESSION['visualizando_de']);
    }

    public static function usuarioOriginal()
    {
        return isset($_SESSION['visualizando_de']) ? $_SESSION['visualizando_de'] : null;
    }

    public static function pararVisualizacaoComo()
    {
        if (!isset($_SESSION['visualizando_de'])) {
            return;
        }

        $original = $_SESSION['visualizando_de'];
        $_SESSION['usuario_id'] = $original['usuario_id'];
        $_SESSION['usuario_nome'] = $original['usuario_nome'];
        $_SESSION['perfis'] = $original['perfis'];
        unset($_SESSION['visualizando_de']);
    }

    public static function logout()
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    /**
     * Checa se a sessao autenticada ja passou do tempo limite de inatividade.
     * Se sim, encerra a sessao, registra 'logout_timeout' na auditoria e retorna
     * false (quem chamou deve redirecionar pro login). Se nao, atualiza o
     * timestamp de ultima atividade e retorna true.
     */
    public static function validarAtividade($timeoutSegundos)
    {
        if (!self::autenticado()) {
            return true;
        }

        $agora = time();
        $ultima = isset($_SESSION['ultima_atividade']) ? $_SESSION['ultima_atividade'] : $agora;

        if (($agora - $ultima) > $timeoutSegundos) {
            $usuarioId = self::usuarioId();
            self::logout();
            Auditoria::registrar('logout_timeout', 'usuarios', $usuarioId, null, null, 'Sessao expirada por inatividade.', $usuarioId);

            return false;
        }

        $_SESSION['ultima_atividade'] = $agora;

        return true;
    }
}
