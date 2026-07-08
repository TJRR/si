<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class GoogleOAuth
{
    const URL_AUTORIZACAO = 'https://accounts.google.com/o/oauth2/v2/auth';
    const URL_TOKEN = 'https://oauth2.googleapis.com/token';
    const URL_USERINFO = 'https://openidconnect.googleapis.com/v1/userinfo';

    private static $config;

    public static function urlAutorizacao($state)
    {
        $config = self::config();

        $parametros = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
        ];

        return self::URL_AUTORIZACAO . '?' . http_build_query($parametros);
    }

    public static function trocarCodigoPorToken($code)
    {
        $config = self::config();

        $campos = [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ];

        return self::requisitar(self::URL_TOKEN, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($campos),
        ]);
    }

    public static function buscarPerfil($accessToken)
    {
        return self::requisitar(self::URL_USERINFO, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        ]);
    }

    private static function config()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/google.php';
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

        if (!is_array($dados)) {
            return null;
        }

        return $dados;
    }
}
