<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 31: fluxo "Service Account + Domain-Wide Delegation" do Google
 * (RFC 7523, JWT Bearer) - impersona um e-mail do dominio institucional
 * (@tjrr.jus.br) sem consentimento individual, ao contrario do login social
 * (App\Core\GoogleOAuth, que fica intocado). So existe UMA credencial no
 * sistema inteiro (config/google_calendar.php); ver DeployFase31.md para o
 * setup completo no Google Cloud/Workspace.
 *
 * Curl cru, mesmo estilo de GoogleOAuth/ItiValidadorService - sem lib nova
 * via Composer. Fail-soft: qualquer falha (config vazia, chave invalida,
 * rede fora, escopo nao autorizado) devolve null, nunca lanca excecao -
 * quem chama decide a mensagem pro usuario.
 */
class GoogleServiceAccountAuth
{
    const URL_TOKEN_PADRAO = 'https://oauth2.googleapis.com/token';
    const GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    const DURACAO_TOKEN_SEGUNDOS = 3600;

    private static $config;

    /**
     * Troca um JWT assinado pela chave da Service Account por um access
     * token de curta duracao, impersonando $emailOrganizador. Nao
     * persiste/cacheia entre requests (token dura so 1h, custo de gerar um
     * novo por request e' desprezivel perto de manter estado compartilhado
     * sem fila/cache no projeto).
     */
    public static function obterAccessToken($emailOrganizador, array $escopos)
    {
        $config = self::config();

        if (empty($config['client_email']) || empty($config['private_key'])) {
            return null;
        }

        $jwt = self::montarJwtAssinado($config, $emailOrganizador, $escopos);

        if ($jwt === null) {
            return null;
        }

        $urlToken = !empty($config['token_uri']) ? $config['token_uri'] : self::URL_TOKEN_PADRAO;

        $resposta = self::requisitar($urlToken, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => self::GRANT_TYPE,
                'assertion' => $jwt,
            ]),
        ]);

        return isset($resposta['access_token']) ? $resposta['access_token'] : null;
    }

    private static function montarJwtAssinado(array $config, $emailOrganizador, array $escopos)
    {
        $agora = time();

        $cabecalho = ['alg' => 'RS256', 'typ' => 'JWT'];
        $reivindicacoes = [
            'iss' => $config['client_email'],
            'scope' => implode(' ', $escopos),
            'aud' => !empty($config['token_uri']) ? $config['token_uri'] : self::URL_TOKEN_PADRAO,
            'iat' => $agora,
            'exp' => $agora + self::DURACAO_TOKEN_SEGUNDOS,
            'sub' => $emailOrganizador,
        ];

        $segmentos = [
            self::base64UrlEncode(json_encode($cabecalho)),
            self::base64UrlEncode(json_encode($reivindicacoes)),
        ];

        $dadosParaAssinar = implode('.', $segmentos);
        $assinatura = '';

        $assinado = @openssl_sign($dadosParaAssinar, $assinatura, $config['private_key'], OPENSSL_ALGO_SHA256);

        if (!$assinado) {
            return null;
        }

        $segmentos[] = self::base64UrlEncode($assinatura);

        return implode('.', $segmentos);
    }

    private static function base64UrlEncode($dados)
    {
        return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
    }

    private static function config()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/google_calendar.php';
        }

        return self::$config;
    }

    private static function requisitar($url, array $opcoesCurl)
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, $opcoesCurl + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $corpo = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($corpo === false || $erroCurl !== '') {
            return null;
        }

        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            return null;
        }

        $dados = json_decode($corpo, true);

        return is_array($dados) ? $dados : null;
    }
}
