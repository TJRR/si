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
    // Em producao: preencher 'user'/'pass' com a conta e senha de app do Google Workspace
    // ja validadas no spike tecnico (smtp.gmail.com:587). Vazio = notificacoes ficam
    // registradas como 'falhou' sem quebrar o restante da aplicacao.
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'from_email' => 'npi@tjrr.jus.br',
        'from_name' => 'Premio de Inovacao TJRR',
    ],
];
