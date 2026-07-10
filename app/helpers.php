<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

function config($chave)
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }

    return isset($config[$chave]) ? $config[$chave] : null;
}

function url($rota)
{
    return config('base_path') . '/index.php?r=' . $rota;
}

function urlAbsoluta($rota)
{
    $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

    return $esquema . '://' . $host . url($rota);
}
