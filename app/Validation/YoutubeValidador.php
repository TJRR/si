<?php

namespace App\Validation;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class YoutubeValidador
{
    public static function valido($url)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        $padrao = '#^https?://(www\.)?(youtube\.com/(watch\?v=|embed/|shorts/)[\w-]+|youtu\.be/[\w-]+)([&?].*)?$#i';

        return (bool) preg_match($padrao, $url);
    }

    public static function extrairId($url)
    {
        $padrao = '#^https?://(www\.)?(youtube\.com/(?:watch\?v=|embed/|shorts/)([\w-]+)|youtu\.be/([\w-]+))#i';

        if (!preg_match($padrao, trim((string) $url), $grupos)) {
            return null;
        }

        return $grupos[3] !== '' ? $grupos[3] : $grupos[4];
    }
}
