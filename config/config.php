<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

$local = require __DIR__ . '/local.php';

return [
    'base_path' => $local['app']['base_path'] ?? '',
    'env' => $local['app']['env'] ?? 'production',
    'timezone' => 'America/Boa_Vista',
];
