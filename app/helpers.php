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

/**
 * Monta o HTML do breadcrumb a partir de um array de ['rotulo' => ..., 'url' => ...].
 * O item sem 'url' (normalmente o ultimo) vira texto simples, nao link.
 */
function breadcrumb_html(array $itens)
{
    $partes = [];

    foreach ($itens as $item) {
        $rotulo = htmlspecialchars($item['rotulo'], ENT_QUOTES, 'UTF-8');
        $partes[] = !empty($item['url']) ? '<a href="' . url($item['url']) . '">' . $rotulo . '</a>' : $rotulo;
    }

    return implode(' &gt; ', $partes);
}
