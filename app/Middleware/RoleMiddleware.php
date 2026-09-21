<?php

namespace App\Middleware;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;

class RoleMiddleware
{
    public static function exigir(array $perfis, $concursoId = null)
    {
        if (!Auth::autenticado()) {
            header('Location: ' . url('auth/login'));
            exit;
        }

        foreach ($perfis as $perfil) {
            if (Auth::temPerfil($perfil, $concursoId)) {
                return;
            }
        }

        self::negarComFlash();
    }

    /**
     * Igual a exigir(), mas aceita o perfil vinculado a QUALQUER concurso (nao so
     * globalmente ou a um concurso especifico). Usada em telas onde o proprio metodo
     * do controller faz a checagem fina por concurso depois — aqui so validamos que
     * o usuario tem o perfil em algum lugar antes de deixar entrar na area.
     */
    public static function exigirEmQualquerConcurso(array $perfis)
    {
        if (!Auth::autenticado()) {
            header('Location: ' . url('auth/login'));
            exit;
        }

        foreach ($perfis as $perfil) {
            if (Auth::possuiPerfil($perfil)) {
                return;
            }
        }

        self::negarComFlash();
    }

    /**
     * Fase 45 (correcao pos-teste de fumaca): antes disso, falta de
     * permissao jogava a pessoa numa pagina em branco fora do layout, com
     * texto cru, sem seguir a convencao de cores sucesso/alerta/erro
     * (verde/laranja/vermelho) ja estabelecida no resto do sistema. Agora
     * grava flashErro() (vermelho) e volta pra de onde a pessoa veio (se for
     * seguro, ver destinoSeguro()) ou pro painel do proprio perfil - nunca
     * deixa a pessoa fora do sistema.
     */
    private static function negarComFlash()
    {
        flashErro('Acesso negado: você não tem permissão para executar esta ação.');
        header('Location: ' . self::destinoSeguro());
        exit;
    }

    /**
     * HTTP_REFERER e' cabecalho controlado pelo CLIENTE - usa-lo sem validar
     * abriria um open redirect (um link malicioso poderia forjar o Referer
     * para mandar a pessoa, apos o "acesso negado", a um site externo com a
     * URL do proprio sistema aparecendo antes do clique). So aceita o
     * Referer como destino se for da MESMA origem (mesmo host, esquema
     * http/https, caminho relativo) - qualquer coisa fora disso cai no
     * painel do proprio perfil.
     */
    private static function destinoSeguro()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

        if ($referer !== null) {
            $host = parse_url($referer, PHP_URL_HOST);
            $porta = parse_url($referer, PHP_URL_PORT);
            // parse_url() devolve o host SEM a porta - em ambiente com porta
            // explicita na URL (ex.: localhost:8090 em desenvolvimento),
            // comparar so' o host contra HTTP_HOST (que INCLUI a porta)
            // nunca bateria, fazendo cair sempre no destino padrao mesmo com
            // um Referer legitimo da mesma origem.
            $autoridade = $porta !== null ? $host . ':' . $porta : $host;
            $esquema = parse_url($referer, PHP_URL_SCHEME);
            $caminho = parse_url($referer, PHP_URL_PATH);

            if ($autoridade === $_SERVER['HTTP_HOST']
                && in_array($esquema, ['http', 'https'], true)
                && $caminho !== null && strpos($caminho, '/') === 0
            ) {
                return $referer;
            }
        }

        return url(Auth::destinoPainel());
    }
}
