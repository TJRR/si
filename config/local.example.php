<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'db' => [
        'host' => 'db',
        'port' => '3306',
        'name' => 'npi_si_dev',
        'user' => 'npi_si',
        'pass' => 'si_dev_pass',
    ],
    'app' => [
        'base_path' => '',
        'env' => 'local',
    ],
    'google' => [
        'client_id' => 'SEU_CLIENT_ID.apps.googleusercontent.com',
        'client_secret' => 'SEU_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost:8090/index.php?r=auth/googleCallback',
    ],
];
